<?php
/**
 * Calculates and saves the bugginess values for a commit.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Mine;

class BugginessCalculator
{
    use \Risk\Stats;

    // Weights should add up to 1
    CONST BISECT_WEIGHT = .9;
    CONST DIFF_WEIGHT = .1;


    protected $witness;

	public function __construct()
	{
        $this->witness = new \Risk\Witness();
	}

    /**
     * Go through the database and calculate a bugginess value for each commit.
     */
    public function assign_bugginess_to_all_commits()
    {
        // This uses inefficient queries, but is separated nicely logically.

        $commit_datastore = new \Risk\Datastore\Commit();
        $commits = $commit_datastore->select_unprocessed_by_bugginess();

        $this->witness->log_information('Will calculate bugginess for ' . count($commits) . ' commits');

        foreach($commits as $commit)
        {
            $bugginess = $this->commit_bugginess($commit);
            $commit_datastore->update_bugginess($commit, $bugginess);
        }
    }

    /**
     * Answers: How buggy is this commit overall? (i.e. How much trouble has this commit caused?)
     *
     * This adds up the ticket-commit bugginess numbers for each (commit,ticket-caused-by-this-commit) pair,
     * weighting the values according to the ticket_bugginess (which in turn is based on ticket's priority).
     *
     * @param \Risk\Model\Commit $commit
     * @return float
     */

    protected function commit_bugginess(\Risk\Model\Commit $commit)
    {
        $ticket_datastore = new \Risk\Datastore\Ticket();
        $bugs_caused_by_this_commit = $ticket_datastore->select_bugs_caused_by_this_commit($commit);

        $this->witness->log_verbose($this->count++ . ' Found ' . count($bugs_caused_by_this_commit) . ' bugs caused by this commit');

        $bugginess = 0;
        foreach($bugs_caused_by_this_commit as $bug)
        {
            $bugginess += $this->ticket_commit_bugginess($bug, $commit) * $this->ticket_bugginess($bug);
        }

        $this->witness->log_verbose('Commit ' . $commit->id() . ' received a total bugginess score of ' . $bugginess);

        return $bugginess;
    }

    /**
     * Answers: How much can we blame this commit for causing this bug (ticket)?
     *
     * This considers both DIFF and BISECT methods, weighting them according to how
     * reliable they are. If other methods of determining bug-introducing commits are
     * introduced, then they should be added here and weighted accordingly (with weights
     * summing up to 1). Always returns a bugginess in [0,1].
     *
     * @param \Risk\Model\Ticket $ticket
     * @param \Risk\Model\Commit $commit
     * @return float
     */
    public function ticket_commit_bugginess(\Risk\Model\Ticket $ticket, \Risk\Model\Commit $commit)
    {
        $commit_datastore = new \Risk\Datastore\Commit();

        $bisected_commits = $commit_datastore->select_bisected_by_ticket($ticket); // For now always one entry
        $diffed_commits = $commit_datastore->select_diffed_by_ticket($ticket);

        $bugginess = 0;

        if($bisected_commits)
        {
            // If this commit was bisected to this ticket, then we are 90% certain this commit caused this bug.
            if(in_array($commit, $bisected_commits))
            {
                $bugginess = self::BISECT_WEIGHT * $this->bisected_commit_bugginess($commit, $ticket);
            }

            // If there's a bisected commit, then we are only 10% sure that a commit found by DIFF caused the bug.
            // In the case that it was found by BISECT and DIFF, then this allows the bugginess to reach 1.
            if(in_array($commit, $diffed_commits))
            {
                $bugginess += self::DIFF_WEIGHT * $this->diffed_commit_bugginess($commit, $ticket);
            }
        }
        else if(in_array($commit, $diffed_commits))
        {
            // No bisected commit was found, so DIFFed commits are the best we have
            // Still, we want it to be scored less than bisected commits, so use the
            // bisected weight as the maximum value.
            $bugginess = self::BISECT_WEIGHT * $this->diffed_commit_bugginess($commit, $ticket);
        }

        $this->witness->log_information(($bugginess*100) . '% likelihood for causing ticket ' . $ticket->jira_id() . ' (' . $ticket->priority() . ')');

        return $bugginess;
    }

    /**
     * Answers: given a bisected commit that certainly caused a bug, what bugginess would we assign to that commit?
     *
     * Since there's always at most one bisected commit, we don't need to distribute the blame, so return 1.
     *
     * @param \Risk\Model\Commit $commit
     * @param \Risk\Model\Ticket $ticket
     * @return float
     */
    protected function bisected_commit_bugginess(\Risk\Model\Commit $commit, \Risk\Model\Ticket $ticket)
    {
        return 1;
    }

    /**
     * Answers: given a commit (of potentially many) that was DIFFed to a ticket, how much blame can we assign
     * to this specific commit for causing the bug?
     *
     * We get the collection of DIFFed commits for this ticket, and proportion the blame on this commit
     * according to how many bug-causing lines this one had for this ticket compared to the total number of
     * bug-causing lines.
     *
     * @param \Risk\Model\Commit $commit
     * @param \Risk\Model\Ticket $ticket
     * @return float
     */
    protected function diffed_commit_bugginess(\Risk\Model\Commit $commit, \Risk\Model\Ticket $ticket)
    {
        // amount according to proportion of lines changed by this commit versus others for the same ticket
        $clue_datastore = new \Risk\Datastore\Clue();

        $total_lines_changed = count($clue_datastore->select_diffed_by_ticket($ticket));
        $lines_changed_by_this_commit = count($clue_datastore->select_diffed_by_ticket_and_commit($ticket, $commit));

        $bugginess = ( $lines_changed_by_this_commit / $total_lines_changed );

        return $bugginess;
    }

    /**
     * Answers: how buggy is this ticket?
     *
     * Since commits that cause blocker bugs should be considered more buggy than those that caused minor bugs,
     * we use the priority to determine the bugginess. Specifically it takes the inverse of the SLA (in days).
     *
     * We then scale this value so that it's between .5 and 1, so that no matter the priority, the value is at
     * least more "buggy" than "not buggy".
     *
     * Since commits that cause
     * @param $ticket
     * @return float
     */
    protected function ticket_bugginess($ticket)
    {
        $sla = $ticket->sla();
        return ( 1 / $sla ) * .5 + .5;
    }

}
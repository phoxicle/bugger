<?php
/**
 * Handler for the risk_utils CLI options.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9//13
 */

namespace Risk;

class Utils
{
	protected $witness;
    protected $git;

	/**
	 *
	 */
	public function __construct()
	{
        $this->witness = new \Risk\Witness();
        $this->git = new \Risk\External\Git();
        $this->witness->log_verbose('Starting risk utils');
	}

    /**
     * Check what the bug-introducing commit algorithm
     * will output for a given commit.
     *
     * @param $commit_hash
     */
    public function guess($commit_hash)
    {
        $this->witness->log_verbose('Guessing bug-introducing commits...' . "\n");
        $this->witness->log_warning('Note that you must be in your git top-level directory for this to work properly.' . "\n");
        $fixing_commit = $this->git->get_commit($commit_hash);

        $guesser = new \Risk\Mine\Guesser();

        $introducing_commit_hashes = $guesser->get_introducing_commit_hashes_for_git_commit($fixing_commit);

        $introducing_commit_hashes = array_unique($introducing_commit_hashes);

        $this->witness->log_information('For ticket associated with commit ' . $commit_hash . ', the following commits are possible causes:'."\n");
        foreach($introducing_commit_hashes as $hash)
        {
            $git_commit = $this->git->get_commit($hash);
            $this->witness->log_verbose($git_commit->short_hash() . ': ' . $git_commit->subject() . ' (' . date('m/d/y', $git_commit->get_author_date()) . ')' . "\n");
        }

        $this->witness->log_information('Historically, there is approximately a 50% chance that this list indeed includes the bug-introducing commit.');
    }

    /**
     * Get the bugginess for a commit.
     *
     * @param $hash
     */
    public function bugginess($hash)
    {
        $this->witness->log_verbose('Retrieving commit bugginess...' . "\n");
        $this->witness->log_warning('Note that only commits and tickets with dates between '
                            . \Risk\Mine::$start_date . ' to ' . \Risk\Mine::$end_date .
                            ' from the v5-dev branch are stored in the database and can possibly be returned by this utility.'."\n");

        $commit_datastore = new \Risk\Datastore\Commit();
        $commit = $commit_datastore->select_by_hash($hash);

        if($commit)
        {
            $this->witness->log_information('Commit ' . $hash . ' has caused the equivalent of approximately: ');
            $this->witness->log_verbose($commit->bugginess());
            $this->witness->log_information('blocker type bugs, based on possibly causing the below bugs:' . "\n");

            $calculator = new \Risk\Mine\BugginessCalculator();
            $ticket_datastore = new \Risk\Datastore\Ticket();

            $this->witness->log_verbose('Found by BLAME method:');
            $diffed_tickets = $ticket_datastore->select_bugs_caused_by_commit_and_method($commit, \Risk\Mine\Guesser::DETERMINED_BY_DIFF);
            foreach($diffed_tickets as $ticket)
            {
                // outputs text
                $calculator->ticket_commit_bugginess($ticket, $commit);
            }

            $this->witness->log_verbose("\n".'Found by BISECT method:');
            $bisected_tickets = $ticket_datastore->select_bugs_caused_by_commit_and_method($commit, \Risk\Mine\BisectFinder::DETERMINED_BY_BISECT);
            foreach($bisected_tickets as $ticket)
            {
                // outputs text
                $calculator->ticket_commit_bugginess($ticket, $commit);
            }
        }
        else
        {
            $this->witness->log_error('Commit not found in database.');
        }
    }

}

<?php
/**
 * Guesses BUG-INTRODUCING commits according to the DIFF method.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */
namespace Risk\Mine;

class Guesser
{
    use \Risk\Stats;

    protected $witness;
    protected $git;

    CONST DETERMINED_BY_DIFF = 1;

	public function __construct()
	{
        $this->witness = new \Risk\Witness();
        $this->git = new \Risk\External\Git();
	}

    /**
     *
     */
    public function find_clues()
    {
        $ticket_datastore = new \Risk\Datastore\Ticket();
        $bugs = $ticket_datastore->select_bugs_unprocessed_by_diff();

        $this->witness->log_information("Will mark commits for " . count($bugs) . " bugs by DIFF method.");

        $clue_datastore = new \Risk\Datastore\Clue();

        foreach($bugs as $bug)
        {
            $this->witness->log_verbose($this->count++ . " Marking bug " . $bug->jira_id());

            $introducing_commits = $this->get_introducing_commits_for_ticket_by_diff($bug);

            foreach($introducing_commits as $commit)
            {
                $clue_datastore->insert_clue($bug, $commit, self::DETERMINED_BY_DIFF);
                self::increment('clues_inserted');
            }

            $ticket_datastore->mark_bug_as_processed_by_diff($bug);
        }
    }

    /**
     * Given a JIRA ticket (typically a bug), retrieve the bug-introducing commits.
     *
     * @param \Risk\Model\Ticket $bug
     * @return array<\Risk\Model\Commit>
     */
    protected function get_introducing_commits_for_ticket_by_diff(\Risk\Model\Ticket $bug)
    {
        $commit_datastore = new \Risk\Datastore\Commit();

        $bug_fixing_commit = $commit_datastore->select_by_ticket($bug);

        // Get associated \Risk\External\Commit object

        $bug_fixing_git_commit = $this->git->get_commit($bug_fixing_commit->hash());
        $introducing_commit_hashes = $this->get_introducing_commit_hashes_for_git_commit($bug_fixing_git_commit);

        $commits = [];
        foreach($introducing_commit_hashes as $hash)
        {
            $commit = $commit_datastore->select_by_hash($hash);

            // If the commit found is not in our DB, don't insert it (these were probably too old for our dataset)

            if($commit)
            {
                $commits[] = $commit;
            }
            else
            {
                self::increment('commits_not_found_in_db');
            }
        }

        $this->witness->log_verbose(count($commits) . ' of ' . count($introducing_commit_hashes) . ' introducing hashes were in the DB');

        return $commits;
    }



    /**
     * Given a bug-fixing commit, make a guess as to which commits caused the bug originally.
     *
     * It does this by looking at each line changed (deleted) by the bug-fixing commit,
     * and getting the previous commit to change that line.
     *
     * @param \Risk\External\Commit $fixing_commit
     * @return array of commit objects
     */
    public function get_introducing_commit_hashes_for_git_commit(\Risk\External\Commit $fixing_commit){

        // Get files changed by fixing commit

        $changed_files = $fixing_commit->changed_files(null, true);

        // For each line, get lines changed

        $introducing_commit_hashes = [];
        foreach($changed_files as $altered_file){

            // Don't inspect test files

            if($altered_file->is_test_file()) continue;

            // For each deleted line, run git blame to retrieve last commit

            $deleted_lines = $altered_file->deleted_lines();

            if(!$deleted_lines)
            {
                self::increment('no_lines_deleted');
                $this->witness->log_warning('No lines were deleted or modified by this commit. Giving up on guessing :*(');
            }

            foreach($deleted_lines as $line_number => $altered_line)
            {
                if($altered_line->is_probably_comment()) continue;

                $commit_data = $this->git->blame($altered_file->name(),
                        $line_number, '+1', $fixing_commit->hash().'^');

                // Since we ran git blame for single lines, we know there's only one element in the data

                $commit_datum = reset($commit_data);

                $short_hash = $commit_datum['commit'];
                $hash = $this->git->get_long_hash($short_hash);
                $introducing_commit_hashes[] = $hash;
            }
        }

        return $introducing_commit_hashes;
    }

}
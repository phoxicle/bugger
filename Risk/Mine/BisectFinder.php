<?php
/**
 * Contains all code needed to detect \Risk\External\Git Bisect output in a ticket.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Mine;

class BisectFinder
{
    use \Risk\Stats;

    protected $witness;
    protected $git;

    CONST DETERMINED_BY_BISECT = 2;

	public function __construct()
	{
        $this->witness = new \Risk\Witness();
        $this->git = new \Risk\External\Git();
	}

    public function find_clues()
    {
        $ticket_datastore = new \Risk\Datastore\Ticket();
        $bugs = $ticket_datastore->select_bugs_unprocessed_by_bisect();

        $this->witness->log_information("Will find clues for " . count($bugs) . " bugs by BISECT method.");

        $clue_datastore = new \Risk\Datastore\Clue();

        foreach($bugs as $bug)
        {
            $this->witness->log_verbose($this->count++ . " Marking bug " . $bug->jira_id());

            $bisected_commit = $this->get_bisected_commit_for_ticket($bug);

            if($bisected_commit)
            {
                $this->witness->log_verbose("Inserting bisected commit " . $bisected_commit->hash());
                self::increment('bisected_commits_inserted');
                $clue_datastore->insert_clue($bug, $bisected_commit, self::DETERMINED_BY_BISECT);
            }

            $ticket_datastore->mark_bug_as_processed_by_bisect($bug);
        }
    }

    protected function get_bisected_commit_for_ticket(\Risk\Model\Ticket $ticket)
    {
        // Fake tickets representing reverts are not applicable to the bisect method.
        if($ticket->is_revert()) return;

        // Get corresponding \Risk\External\Ticket
        $jira = new \Risk\External\Jira();
        $ticket_details = $jira->get_ticket_details($ticket->jira_id());

        $hash = $this->get_bisected_hash_in_ticket($ticket_details);

        // Temporary, just for our info
        if($hash)
        {
            $this->witness->log_information('Found hash ' . $hash);
            self::increment('bisected_commits_found');

            $in_dev = $this->git->is_commit_hash_on_remote($hash, 'v5-dev');

            if (!$in_dev)
            {
                $this->witness->log_warning('Commit ' . $hash . ' not found in v5-dev');
                self::increment('commits_not_in_v5-dev');
            }
        }

        $commit_datastore = new \Risk\Datastore\Commit();
        $commit = $commit_datastore->select_by_hash($hash);

        return $commit;

    }

    protected function match_is_first_bad_commit($text)
    {
        // We match the change ID instead of commit hash since it doesn't depend on the branch where bisecting was done
        $filter = '/(?:([a-zA-Z0-9]*?) is the first bad commit)/';

        preg_match($filter, $text, $matches);

        if($matches)
        {
            // There can be only one.
            return $matches[1];
        }
        else
        {
            // Log if the word bisect was found, but not caught by regex above
            preg_match('/.*bisect.*/', $text, $matches);
            if ($matches) self::increment('bisect_not_caught');
        }
    }

    protected function get_bisected_hash_in_ticket($ticket_details)
    {

        if(isset($ticket_details['description']['value']))
        {
            $description = $ticket_details['description']['value'];
            $match = $this->match_is_first_bad_commit($description);
        }

        // If no match in body, check comments
        if(!$match)
        {
            $comments = $ticket_details['comment']['value'];
            foreach($comments as $comment)
            {
                $body = $comment['body'];
                $match = $this->match_is_first_bad_commit($body);

                // Stop after the first match
                if($match) break;
            }
        }

        return $match;
    }
}
<?php
/**
 * Handles retrieval and storage of all commit/ticket objects.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */
namespace Risk\Mine;

class Retriever
{
    use \Risk\Stats;

    protected $witness;
    protected $git;



	public function __construct()
	{
        $this->witness = new \Risk\Witness();
        $this->git = new \Risk\External\Git();
	}

    /**
     * Retrieve all commits and the tickets associated with them. These commits
     * are saved as the fixing_commits for the ticket.
     *
     * Post-condition: All commits and tickets of interest are stored in DB.
     */
    public function retrieve_commits_and_tickets($start_date, $end_date){

        $this->witness->log_information('Retrieving commits');

        // Get all commits

        $commits = $this->git->all_commits_between($start_date, $end_date);

        // Store each commit in our DB
        $total_count = count($commits);
        $this->witness->log_warning('Will process ' . $total_count . ' commits');

        $ticket_datastore = new \Risk\Datastore\Ticket();

        foreach($commits as $commit)
        {
            $this->witness->log_verbose($this->count++ . ' Processing commit ' . $commit->hash());
            self::increment('commits_retrieved');

            $ticket = $this->retrieve_valid_ticket_for_commit($commit);

            // If there's a valid ticket, insert the commit and ticket into our db

            if($ticket)
            {
                $ticket_row_id = $ticket_datastore->insert_ticket_with_commit($ticket, $commit);

                if(!$ticket_row_id)
                {
                    self::increment('failed_inserting_ticket_with_commit');
                }
                else
                {
                    self::increment('commits_inserted');
                }
            }
        }
    }



    /**
     * Retrieve a valid ticket for a given commit.
     *
     * There are a number of requirements:
     * - There must be only one ticket associated with commit
     * - It must not be an exempt (magic) ticket
     * - It must be closed.
     *
     * @param \Risk\External\Commit $commit
     * @return \Risk\External\Ticket $ticket or null
     */
    protected function retrieve_valid_ticket_for_commit($commit)
    {

        // Get all tickets for the commit
        try
        {
            $tickets = $commit->jira_tickets();
        }
        catch(\Risk\External\Tickets_Not_Found_Exception $e)
        {
            self::increment('no_ticket_count');
            return;
        }

        // If no tickets, skip this entry
        if(empty($tickets))
        {
            self::increment('no_ticket_count');
            return;
        }

        // If multiple tickets for this commit, skip this entry
        if(sizeof($tickets) > 1)
        {
            self::increment('multiple_ticket_count');
            return;
        }

        $ticket = reset($tickets);

        $this->witness->log_verbose('Found ticket ' . $ticket->ticket_number());

        // If ticket is not closed or is marked "exempt", skip this entry
        if($ticket->is_exempt())
        {
            self::increment('exempt_count');
            return;
        }

        if($ticket->status() != 'Closed')
        {
            self::increment('open_count');
            return;
        }

        return $ticket;

    }
}
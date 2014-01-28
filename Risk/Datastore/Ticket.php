<?php
/**
 * All methods related to storage and retrieval of \Risk\Datastore\Commit objects.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */

namespace Risk\Datastore;

class Ticket
{

    protected $witness;

    public function __construct()
    {
        $this->witness = new \Risk\Witness();
    }

    /**
     * Insert a ticket paired with its fixing commit.
     *
     * @param \Risk\External\\Risk\External\Ticket $ticket
     * @param \Risk\External\Commit $commit
     */
    public function insert_ticket_with_commit(\Risk\External\Ticket $ticket, \Risk\External\Commit $commit)
    {
        try
        {
            $commit_datastore = new \Risk\Datastore\Commit();

            $commit_row_id = $commit_datastore->insert_commit_from_object($commit);

            try{
                $ticket_row_id = $this->insert_ticket_from_object($ticket, $commit_row_id, $commit->is_revert());

                return $ticket_row_id;
            }
            catch(DB_ExecutionException $e)
            {
                // Oh, consistency
                $commit_datastore->delete_by_id($commit_row_id);

                $this->witness->log_error("Inserting ticket failed, so deleting commit");
            }
        }
        catch(DB_ExecutionException $e)
        {
            $this->witness->log_error("Commit already exists, skipping ticket insert");
        }
    }



    /**
     * Extract needed fields from a \Risk\External\Ticket object then call insert.
     *
     * @param \Risk\External\Ticket $ticket
     * @param $commit_row_id
     * @param $is_revert Whether this row represents a reverted ticket.
     * @return int row ID of new object
     */
    public function insert_ticket_from_object(\Risk\External\Ticket $ticket, $commit_row_id, $is_revert = false)
    {
        $ticket_number = $ticket->ticket_number();
        $is_bug = $ticket->is_bug();

        // Signal that this rows represents a reverted ticket, rather than a fixed ticket.
        if($is_revert)
        {
            $ticket_number .= '_REVERT';
            $is_bug = true;
        }

        $ticket_priority_info = $ticket->priority();

        return $this->insert_ticket($ticket_number, (int)$is_bug,
            $ticket_priority_info['name'], $ticket->created(), $commit_row_id);
    }

    /**
     * Insert a new risk ticket row.
     *
     * @param $jira_id
     * @param $is_bug
     * @param $priority
     * @param $report_date
     * @param $fixing_commit_id
     * @return int row ID of new object.
     */
    public function insert_ticket($jira_id, $is_bug, $priority, $report_date, $fixing_commit_id)
    {
        $model = new \Risk\Model\Ticket();

        $model->set('jira_id', $jira_id);
        $model->set('is_bug', $is_bug);
        $model->set('priority', $priority);
        $model->set('report_date', $report_date);
        $model->set('fixing_commit_id', $fixing_commit_id);

        return $model->save();
    }

    /**
     * Delete a ticket.
     * @param $id
     */
    public function delete_by_id($id)
    {
        $model = $this->select_by_id($id);
        $model->delete();
    }

    /**
     * Select a commit by its database ID.
     *
     * @param $id
     * @return mixed
     */
    public function select_by_id($id)
    {
        $model = new \Risk\Model\Ticket();

        return $model->find_i([
            'id' => $id,
        ]);
    }

    /**
     * Select a commit by its JIRA ID.
     *
     * @param $jira_id
     * @return mixed
     */
    public function select_by_jira_id($jira_id)
    {
        $model = new \Risk\Model\Ticket();

        return $model->find_i([
            'jira_id' => $jira_id,
        ]);
    }

    /**
     * Get tickets of type "bug"
     *
     * @return array
     */
    public function select_bugs()
    {
        $model = new \Risk\Model\Ticket();

        $res = $model->find_all([
            'is_bug' => 1,
        ]);

        return $res;
    }

    /**
     * Get tickets of type "bug" that have "is_processed_by_diff" false.
     *
     * @return array
     */
    public function select_bugs_unprocessed_by_diff()
    {
        $model = new \Risk\Model\Ticket();

        $res = $model->find_all([
            'is_bug' => 1,
            'is_processed_by_diff' => 0
        ]);

        return $res;
    }

    /**
     * Get tickets of type "bug" that have "is_processed_by_bisect" false.
     *
     * @return array
     */
    public function select_bugs_unprocessed_by_bisect()
    {
        $model = new \Risk\Model\Ticket();

        $res = $model->find_all([
            'is_bug' => 1,
            'is_processed_by_bisect' => 0
        ]);

        return $res;
    }

    /**
     * Set "is_processed_by_diff" to true for this ticket.
     *
     * @param \Risk\Model\Ticket $ticket
     * @return array
     */
    public function mark_bug_as_processed_by_diff(\Risk\Model\Ticket $ticket)
    {
        $ticket->set('is_processed_by_diff', 1);
        return $ticket->save();
    }

    /**
     * Set "is_processed_by_bisect" to true for this ticket.
     *
     * @param \Risk\Model\Ticket $ticket
     * @return array
     */
    public function mark_bug_as_processed_by_bisect(\Risk\Model\Ticket $ticket)
    {
        $ticket->set('is_processed_by_bisect', 1);
        return $ticket->save();
    }

    /**
     * Return a unique array of tickets which were in some way caused by this commit.
     *
     * @param \Risk\Model\Commit $commit
     * @return array
     */
    public function select_bugs_caused_by_this_commit(\Risk\Model\Commit $commit)
    {
        $clue_datastore = new \Risk\Datastore\Clue();
        $clues = $clue_datastore->select_by_commit($commit);

        $tickets = [];
        foreach($clues as $clue)
        {
            $ticket_id = $clue->ticket_id();
            if(!isset($tickets[$ticket_id]))
            {
                $tickets[$ticket_id] = $this->select_by_id($ticket_id);
            }
        }

        return array_values($tickets);
    }

    /**
     * Return a unique array of tickets which were in some way caused by this commit.
     *
     * @param \Risk\Model\Commit $commit
     * @param int $method
     * @return array
     */
    public function select_bugs_caused_by_commit_and_method(\Risk\Model\Commit $commit, $method)
    {
        $clue_datastore = new \Risk\Datastore\Clue();
        $clues = $clue_datastore->select_by_commit_and_method($commit, $method);

        $tickets = [];
        foreach($clues as $clue)
        {
            $ticket_id = $clue->ticket_id();
            if(!isset($tickets[$ticket_id]))
            {
                $tickets[$ticket_id] = $this->select_by_id($ticket_id);
            }
        }

        return array_values($tickets);
    }


}


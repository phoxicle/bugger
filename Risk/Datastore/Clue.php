<?php
/**
 * All methods retrieving data \Risk\Model\Clue objects from database.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @see \Risk\Model\Clue
 * @since 9/9/13
 */

namespace Risk\Datastore;

class Clue
{

    /**
     * Insert a new clue row. This is the table
     * that links bug-type tickets with the commits that introduced the bugs.
     *
     * @param $commit_id
     * @param $ticket_id
     * @param $determined_by
     * @return int row ID of new object
     */
    public function insert_clue(\Risk\Model\Ticket $ticket, \Risk\Model\Commit $commit, $determined_by)
    {
        $model = new \Risk\Model\Clue();

        $model->set('commit_id', $commit->id());
        $model->set('ticket_id', $ticket->id());
        $model->set('determined_by', $determined_by);

        return $model->save();
    }

    /**
     * Select clues marked as introducing this bug.
     *
     * @param \Risk\Model\Ticket $ticket
     * @return \Risk\Model\Clue
     */
    public function select_by_ticket(\Risk\Model\Ticket $ticket)
    {
        $model = new \Risk\Model\Clue();

        return $model->find_all([
           'ticket_id' => $ticket->id()
        ]);
    }

    /**
     * Select clues marked as introduced by this commit.
     *
     * @param \Risk\Model\Ticket $commit
     * @return \Risk\Model\Clue
     */
    public function select_by_commit(\Risk\Model\Commit $commit)
    {
        $model = new \Risk\Model\Clue();

        return $model->find_all([
           'commit_id' => $commit->id()
        ]);
    }

    /**
     * Select clues determined by BISECT method.
     *
     * @param \Risk\Model\Ticket $ticket
     * @return \Risk\Model\Clue
     */
    public function select_bisected_by_ticket(\Risk\Model\Ticket $ticket)
    {
        $model = new \Risk\Model\Clue();

        return $model->find_all([
            'ticket_id' => $ticket->id(),
            'determined_by' => \Risk\Mine_BisectFinder::DETERMINED_BY_BISECT
        ]);
    }

    /**
    * Select clues determined by DIFF method.
    *
    * @param \Risk\Model\Ticket $ticket
    * @return array
    */
    public function select_diffed_by_ticket(\Risk\Model\Ticket $ticket)
    {
        $model = new \Risk\Model\Clue();

        return $model->find_all([
            'ticket_id' => $ticket->id(),
            'determined_by' => \Risk\Mine\Guesser::DETERMINED_BY_DIFF
        ]);
    }

    /**
    * Select clues determined by DIFF method by commit.
    *
    * @param \Risk\Model\Commit $commit
    * @return array
    */
    public function select_by_commit_and_method(\Risk\Model\Commit $commit, $method)
    {
        $model = new \Risk\Model\Clue();

        return $model->find_all([
            'commit_id' => $commit->id(),
            'determined_by' => $method
        ]);
    }

    /**
     * Select clues by both the bug and commit.
     *
     * @param $ticket
     * @param $commit
     * @return \Risk\Model\Clue
     */
    public function select_diffed_by_ticket_and_commit($ticket, $commit)
    {
        $model = new \Risk\Model\Clue();

        return $model->find_all([
            'ticket_id' => $ticket->id(),
            'commit_id' => $commit->id(),
            'determined_by' => \Risk\Mine\Guesser::DETERMINED_BY_DIFF
        ]);
    }
}


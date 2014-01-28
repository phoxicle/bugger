<?php
/**
 * All methods related to storage and retrieval of \Risk\Model\Commit objects.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @see \Risk\Model\Commit
 * @since 9/9/13
 */

namespace Risk\Datastore;

class Commit
{
    CONST DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Extract needed fields from a \Risk\External\Commit object then call insert.
     *
     * @param \Risk\External\Commit $commit
     * @return int row ID of new object
     */
    public function insert_commit_from_object(\Risk\External\Commit $commit)
    {
        $commit_date = date(self::DATE_FORMAT, $commit->get_commit_date());

        return $this->insert_commit($commit->hash(), $commit_date, $commit->is_revert());
    }

    /**
     * Insert a new risk commit row.
     *
     * @param $commit_hash
     * @param $date MySQL datetime compatible date
     * @param $is_revert
     * @param null $bugginess
     * @return int row ID of new object
     */
    public function insert_commit($hash, $date, $is_revert)
    {
        $model = new \Risk\Model\Commit();

        $model->set('hash', $hash);
        $model->set('date', $date);
        $model->set('is_revert', (int)$is_revert);

        return $model->save();
    }

    public function delete_by_id($id)
    {
        $model = $this->select_by_id($id);
        $model->delete();
    }

    public function select_all()
    {
        $model = new \Risk\Model\Commit();
        return $model->find_all();
    }

    /**
     * Select a commit from our DB by its commit hash.
     *
     * @param $hash
     * @return mixed
     */
    public function select_by_hash($hash)
    {
        $model = new \Risk\Model\Commit();

        return $model->find_i([
          'hash' => $hash
        ]);
    }

    /**
     * Select a commit by its database ID.
     *
     * @param $hash
     * @return mixed
     */
    public function select_by_id($id)
    {
        $model = new \Risk\Model\Commit();

        return $model->find_i([
          'id' => $id
        ]);
    }

    /**
     * Select a commit by the associated ticket.
     *
     * @param \Risk\Model\Ticket $ticket
     * @return mixed
     */
    public function select_by_ticket(\Risk\Model\Ticket $ticket)
    {
        $model = new \Risk\Model\Commit();

        return $model->find_i([
           'id' => $ticket->fixing_commit_id()
        ]);
    }

   /**
    *  Select tickets that have not had the bugginess script run on them.
    *
    * @return array
    */
    public function select_unprocessed_by_bugginess()
    {
       $model = new \Risk\Model\Commit();

        // -1 is the column default
       $res = $model->find_all([
           'bugginess' => -1
       ]);

       return $res;
    }

    /**
     * Set the bugginess value for a commit.
     *
     * @param $commit
     * @param $bugginess
     * @return mixed
     */
    public function update_bugginess($commit, $bugginess)
    {
        $commit->set('bugginess', $bugginess);
        return $commit->save();
    }

    /**
     * Select commits that were BISECTed for this ticket. This is really
     * always one commit, but returns an array for consistency.
     *
     * @param \Risk\Model\Ticket $ticket
     * @return array
     */
    public function select_bisected_by_ticket(\Risk\Model\Ticket $ticket)
    {
        $datastore = new \Risk\Datastore\Clue();

        $bisected = $datastore->select_bisected_by_ticket($ticket);

        return $this->select_by_clues($bisected);
    }

    /**             
     * Select commits that were DIFFed for this ticket.
     * 
     * @param \Risk\Model\Ticket $ticket
     * @return array
     */
    public function select_diffed_by_ticket(\Risk\Model\Ticket $ticket)
    {
        $datastore = new \Risk\Datastore\Clue();

        $diffed = $datastore->select_diffed_by_ticket($ticket);

        return $this->select_by_clues($diffed);
    }

    /**             
     * Get the unique set of commits associated with a set of clues.
     * 
     * @param $clues
     * @return array
     */
    public function select_by_clues($clues)
    {
        $commits = [];
        foreach($clues as $clue)
        {
            $commit_id = $clue->commit_id();
            if(!isset($commits[$commit_id]))
            {
                $commits[$commit_id] = $this->select_by_id($commit_id);
            }
        }

        return array_values($commits);
    }

}


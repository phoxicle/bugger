<?php
/**
 * All methods related to storage and retrieval of \Risk\Model\Metric objects.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @see \Risk\Model\Metric
 * @since 9/9/13
 */

namespace Risk\Datastore;

class Metric
{

    /**
     * Insert a new risk commit row.
     *
     * @param $commit
     * @param $key
     * @param $value
     * @return int row ID of metric object
     */
    public function insert_or_update(\Risk\Model\Commit $commit, $key, $value)
    {
        $model = new \Risk\Model\Metric();

        $result = $model->find_i(array(
            'commit_id' => $commit->id(),
            'key' => $key
        ));

        if($result)
        {
            $result->set('value', $value);
            $result->save();

            return $result->id();
        }
        else
        {
            $model->set('commit_id', $commit->id());
            $model->set('key', $key);
            $model->set('value', $value);

            return $model->save();
        }

    }

    public function select_by_commit_and_key(\Risk\Model\Commit $commit, $key)
    {
        $model = new \Risk\Model\Metric();

        return $model->find_i([
            'commit_id' => $commit->id(),
            'key' => $key
        ]);
    }

    public function select_by_key($key)
    {
        $model = new \Risk\Model\Metric();

        return $model->find_all([
            'key' => $key
        ]);
    }
}


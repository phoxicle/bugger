<?php
/**
 * All methods retrieving \Risk\Model\BugginessMetricsView objects from database.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @see \Risk\Model\BugginessMetricsView
 * @since 9/9/13
 */
namespace Risk\Datastore;

class BugginessMetricsView
{

    /**
     * @param $key the metric key
     */
    public function select_by_key($key)
    {
        $model = new \Risk\Model\BugginessMetricsView();

        return $model->find_all([
           'key' => $key
        ]);
    }
}


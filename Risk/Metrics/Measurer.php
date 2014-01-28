<?php
/**
 * Handles measuring metrics about commits and storing them in the database.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class Measurer {

    use \Risk\Stats;

    protected $witness;



    public function __construct()
    {
        $this->witness = new \Risk\Witness();
    }

    /**
     * Measure a single metric for every commit.
     *
     * @param $key
     */
    public function measure_metric($key)
    {
        $commit_datastore = new \Risk\Datastore\Commit();
        $commits = $commit_datastore->select_all();

        $this->witness->log_information('Going to measure metrics for ' . count($commits) . ' commits.');

        $metric_datastore = new \Risk\Datastore\Metric();

        foreach($commits as $commit)
        {
            // Don't recalculate metric if it's already set.

            $metric = $metric_datastore->select_by_commit_and_key($commit, $key);
            if($metric)
            {
                $this->witness->log_verbose('Already measured, skipping');
                continue;
            }

            $this->witness->log_verbose($this->count++ . ' Measuring commit ' . $commit->id());
            $value = $this->measure_metric_for_commit($commit, $key);

            // Null implies that this metric is not applicable for this commit, so skip

            if(is_null($value)) continue;

            $metric_row_id = $metric_datastore->insert_or_update($commit, $key, $value);

            if(!$metric_row_id)
            {
                throw new Exception('Inserting metric failed');
            }
        }

        $this->witness->log_information('Done measuring ' . $key);
    }

    /**
     * Measure a single metric for a single commit.
     *
     * @param \Risk\Model\Commit $commit
     * @param $key
     * @throws Exception
     */
    public function measure_metric_for_commit(\Risk\Model\Commit $commit, $key)
    {
        $class_name = \Risk\Metrics::get_class_name_for_key($key);
        $metric = new $class_name();

        if($metric instanceof IMetric)
        {
            $value = $metric->measure($commit);
            $this->witness->log_verbose('Measured ' . $key . ':' . $value);

            return $value;
        }
        else
        {
            throw new Exception($class_name . ' must be instance of IMetric');
        }
    }
}
<?php

namespace Risk\Predict;

class Trainer
{
    use \Risk\Stats;

    protected $witness;

	public function __construct()
	{
        $this->witness = new \Risk\Witness();
	}

    /**
     * Run the decision tree algorithm on all stored data.
     *
     * @param array $keys
     * @param float $training_split
     */
    public function build_decision_tree(array $keys, $training_split)
    {
        $map = $this->retrieve_all_historical_values_as_map($keys);

        // We don't need the commit IDs
        $values = array_values($map);

        $output = \Risk\R\API::decision_tree($keys, $values, $training_split);

        $this->witness->log_information($output);
    }

    /**
     * This really just converts DB entries into a better format for R.
     *
     * @param $keys The metric keys to retrieve
     * @return array of the form [
     *      1 => ['bugginess' => 'Q1', 'key1' => 'Q1', ...],
     *      2 => ['bugginess' => 'Q2', 'key1' => null, ...],
     *      ...
     * ]
     */
    protected function retrieve_all_historical_values_as_map($keys)
    {
        $commits_with_values = $this->retrieve_commits_with_quantiled_bugginess_as_map();

        // Initialize all metric values to null
        foreach($commits_with_values as $commit_id => &$value_map)
        {
            foreach($keys as $key) $value_map[$key] = null;
        }

        $this->witness->log_verbose('Retrieving metrics');

        // For each metric, set its value in array

        $metric_datastore = new \Risk\Datastore\Metric();
        foreach($keys as $key)
        {
            $metrics = $metric_datastore->select_by_key($key);

            // Gather values

            $commit_ids = $values = [];
            foreach($metrics as $metric)
            {
                $commit_ids[] = $metric->commit_id();
                $values[] = $metric->value();
            }

            // Quantile non-categorical metrics

            $correlator = new \Risk\Metrics\Correlator();

            $metrics = new \Risk\Metrics();
            if(!$metrics->is_categorical_metric($key))
            {
                $this->witness->log_verbose('Converting ' . $key . ' to quantiles');
                $values = $correlator->partition_into_quantiles($values, 4, $key);
            }

            for($i = 0; $i < count($commit_ids); $i++)
            {
                $commits_with_values[$commit_ids[$i]][$key] = $values[$i];
            }
        }

        return $commits_with_values;
    }

    /**
     * Retrieve all commits from DB and return in a hash map.
     *
     * @return array of the format [
     *      1 => ['bugginess' => 'Q1'],
     *      2 => ['buginess' => 'Q2'],
     *      ...
     *  ]
     */
    protected function retrieve_commits_with_quantiled_bugginess_as_map()
    {
        $commit_datastore = new \Risk\Datastore\Commit();
        $commits = $commit_datastore->select_all();

        $this->witness->log_verbose('Retrieving bugginess values');

        $commit_ids = $bugginess_values = [];
        foreach($commits as $commit)
        {
            $commit_ids[] = $commit->id();
            $bugginess_values[] = $commit->bugginess();
        }

        $this->witness->log_verbose('Quantiling bugginess values');

        $correlator = new \Risk\Metrics\Correlator();
        $bugginess_values = $correlator->partition_into_quantiles($bugginess_values);

        $commits_with_bugginess = [];
        for($i = 0; $i < count($commit_ids); $i++)
        {
            $values = ['bugginess' => $bugginess_values[$i]];

            $commits_with_bugginess[$commit_ids[$i]] = $values;
        }

        return $commits_with_bugginess;
    }

}
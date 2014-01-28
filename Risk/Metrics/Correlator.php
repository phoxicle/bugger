<?php
/**
 * Performs all domain logic related to correlations and other statistical techniques.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */

namespace Risk\Metrics;

class Correlator
{
    protected $bugginess_values;
    protected $metric_values;

    protected static $cached_boundaries_suffix = '_boundaries';

    public function __construct()
    {
       $this->witness = new \Risk\Witness();
    }

    /**
     * Correlate a given metric with bugginess
     *
     * @param string $key
     * @throws Exception
     */
    public function correlate($key)
    {
        $metrics = new \Risk\Metrics();
        if($metrics->is_categorical_metric($key))
        {
            $this->witness->log_error('Correlations can only be performed on numerical metrics');
            return;
        }

        $this->init_bugginess_and_metric_values_for_key($key);

        $bugginess_values = $this->bugginess_values[$key];
        $metric_values = $this->metric_values[$key];

        $this->witness->log_information('Performing normality test.');

        // < 3 data points also doesn't make sense
        if(sizeof($metric_values) > 5000 || sizeof($metric_values) < 3)
        {
            $this->witness->log_verbose('Skipping normality test since >5000 data points, so your data will never be normal.');
        }
        else
        {
            $output = \Risk\R\API::normality($metric_values);
            $this->witness->log_verbose($output);
            $this->witness->log_information('If the p-value of the normality test is less than .05, we reject normality.');
        }

        $this->witness->log_information('Calculating correlation!');

        $output = \Risk\R\API::correlation($bugginess_values, $metric_values);

        $this->witness->log_information('Correlation between ' . $key . ' and bugginess is:');
        $this->witness->log_verbose($output);

        $this->witness->log_information('The Pearson correlation is only applicable if the data are normal.');
        $this->witness->log_information('A very generalized guideline for interpreting the correlation coefficients:
                0 : no relation
                0.1 - 0.3 : weak relation
                0.3 - 0.7 : moderate relation
                0.7 - 1.0 : strong relation');
        $this->witness->log_information('where the sign indicates the direction of the relation.');
        $this->witness->log_information('To be significant, the p-value must also be less than 0.05.');
    }

    public function plot($key)
    {
        $metrics = new \Risk\Metrics();
        if($metrics->is_categorical_metric($key))
        {
            $this->witness->log_error('Plots can only be currently be created for numerical metrics');
            return;
        }

        list($bugginess_values, $metric_values) = $this->get_bugginess_and_metric_values_for_key($key);

        $file_path = \Risk\R\API::plot($metric_values, $bugginess_values, $key);
        $this->witness->log_verbose('Plot created at ' . $file_path . ' in your current working directory.');
    }

    /**
     * Correlate the given metric with bugginess using the multiple comparisons method.
     *
     * @param $key
     */
    public function multiple_comparisons($key)
    {
        $this->init_bugginess_and_metric_values_for_key($key);

        $bugginess_values = $this->bugginess_values[$key];
        $metric_values = $this->metric_values[$key];

        $this->witness->log_information('Calculating correlation using multiple comparisons method.');

        // If this is a numerical metric, then we need to split it up in quantiles first

        $class_name = \Risk\Metrics::get_class_name_for_key($key);
        if(!is_subclass_of($class_name, 'Categorical'))
        {
            $this->witness->log_warning('Converting metric values to quantiles.');
            $metric_values = $this->partition_into_quantiles($metric_values);
        }

        if(count(array_unique($metric_values)) > 2)
        {
            $output = \Risk\R\API::multiple_comparisons($bugginess_values, $metric_values);
        }
        else
        {
            $this->witness->log_verbose('Only two unique values, performing npartest.');
            $output = \Risk\R\API::npartest($bugginess_values, $metric_values);
        }


        $this->witness->log_information('Comparisons between ' . $key . ' and bugginess is:');
        $this->witness->log_verbose($output);

        $this->witness->log_information('In general, the goal is to find the ordering between categories suggested by the estimator values.');
    }

    /**
     * Assign each value to a quantile.
     *
     * @param $values
     * @param $num_partitions
     * @return array of same length of values with quantile names.
     */
    public function partition_into_quantiles($values, $num_partitions = 4, $key = '')
    {
        $boundaries = \Risk\R\API::quantile($values, $num_partitions);

        // Duplicates happen if there are too few distinct values for the number of partitions
        $boundaries = $this->remove_duplicate_boundaries($boundaries);

        $this->witness->log_information('Boundaries used for quantiles are: ' . implode(',', $boundaries));

        $quantiled_values = $this->place_in_quantiles_with_boundaries($values, $boundaries);

        if($key)
        {
            $this->set_cached_boundaries($key, $boundaries);
        }

        return $quantiled_values;
    }

    protected function set_cached_boundaries($key, $boundaries)
    {
        $cache = new \Risk\Cache();
        $cache->cache($key . self::$cached_boundaries_suffix, $boundaries);
    }

    public function retrieve_cached_boundaries($key)
    {
        $cache = new \Risk\Cache();
        return $cache->retrieve($key . self::$cached_boundaries_suffix);
    }

    public function place_in_quantiles_with_boundaries($values, $boundaries)
    {
        $quantile_values = [];
        $counts = [];
        foreach($values as $value)
        {
            $q = 1;
            while($boundaries[$q] < $value)
            {
                if(!isset($boundaries[$q+1])) break;
                $q++;
            }

            $quantile = 'Q' . $q;
            $quantile_values[] = $quantile;

            // Maintain a count to report later
            if(!isset($counts[$quantile])) $counts[$quantile] = 0;
            $counts[$quantile]++;
        }

        // Print number of elements in each quantile
        foreach($counts as $quantile => $count)
        {
            $this->witness->log_information('Quantile ' . $quantile . ' contains ' . $count . ' elements');
        }

        return $quantile_values;
    }

    /**
     * Remove any duplicate boundary values.
     *
     * @param $boundaries of the form [min, some, quantile, values, max]
     * @return the form [min, unique, quantile, values, max]
     */
    protected function remove_duplicate_boundaries($boundaries)
    {
        $min = array_shift($boundaries);
        $max = array_pop($boundaries);
        return array_merge([$min], array_unique($boundaries), [$max]);
    }


    /**
     * Get arrays of bugginess and metric values for the given metric.
     *
     * Speeds up processing in case correlate and multiple comparisons are done
     * back to back.
     *
     * @param $key
     * @throws Exception
     */
    protected function init_bugginess_and_metric_values_for_key($key)
    {
        if(isset($this->bugginess_values[$key])) return;

        $bugginess_metrics_datastore = new \Risk\Datastore\BugginessMetricsView();
        $bugginess_metrics = $bugginess_metrics_datastore->select_by_key($key);

        $this->witness->log_information('Found ' . count($bugginess_metrics) . ' values with bugginess');

        foreach($bugginess_metrics as $bugginess_metric)
        {
            $bugginess_values[] = $bugginess_metric->bugginess();
            $metric_values[] = $bugginess_metric->value();
        }

        $this->bugginess_values[$key] = $bugginess_values;
        $this->metric_values[$key] = $metric_values;
    }

    protected function get_bugginess_and_metric_values_for_key($key)
    {
        $this->init_bugginess_and_metric_values_for_key($key);

        return [$this->bugginess_values[$key], $this->metric_values[$key]];
    }

}
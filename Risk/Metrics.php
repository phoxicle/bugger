<?php
/**
 * Handler for the risk_metrics CLI options.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/5/13
 */

namespace Risk;

class Metrics
{
    protected $witness;

    protected static $metric_map = [
        'lines_added' => 'LinesAdded',
        'lines_added_plus_deleted' => 'LinesAddedPlusDeleted',
        'num_patchsets' => 'NumberPatchsets',
        'cc' => 'CyclomaticComplexity',
        'date' => 'Date',
        'num_upvoters' => 'NumberUpvoters',
        'num_files_touched' => 'NumberFilesTouched',
        'method_loc' => 'MethodLOC',
        'num_file_types' => 'NumberFileTypes',
        'day' => 'DayOfWeek',
        'php_or_js' => 'FileTypesPHPOrJS',
        'delta_cc' => 'DeltaCyclomaticComplexity',
        'prev_committers' => 'NumPrevCommittersForFiles',
        'prev_commits' => 'NumPrevCommitsForFiles',
    ];

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
        $measurer = new \Risk\Metrics\Measurer();
        $measurer->measure_metric($key);
    }


    /**
     * Correlate a given metric with bugginess
     *
     * @param string $key
     */
    public function correlate($key)
    {
        $this->witness->log_information('Starting correlation...');

        $correlator = new \Risk\Metrics\Correlator($key);
        $correlator->correlate($key);
    }


    public function plot($key)
    {
        $this->witness->log_information('Plotting...');

        $correlator = new \Risk\Metrics\Correlator($key);
        $correlator->plot($key);
    }

    /**
     * Correlate a given metric with bugginess using the multiple comparisons method.
     *
     * @param string $key
     */
    public function compare_multiple($key)
    {
        $this->witness->log_information('Starting multiple correlation...');

        $correlator = new \Risk\Metrics\Correlator($key);
        $correlator->multiple_comparisons($key);
    }

    /**
     * Get the full class name corresponding to a metric key, according to the metric_map array.
     *
     * @param $key
     * @return string
     * @throws \Risk\Metrics\Exception
     */
    public function get_class_name_for_key($key)
    {
        if(!isset(self::$metric_map[$key]))
        {
            throw new \Risk\Metrics\Exception($key . ' is not a valid metric key');
        }

        $class_name = '\Risk\Metrics\\' . self::$metric_map[$key];

        if(!class_exists($class_name))
        {
            throw new \Risk\Metrics\Exception('Class ' . $class_name . ' does not exist.');
        }

        return $class_name;
    }

    public function get_metric_keys()
    {
        return array_keys(self::$metric_map);
    }

    public function is_categorical_metric($key)
    {
        $class_name =  $this->get_class_name_for_key($key);
        return is_subclass_of($class_name, '\Risk\Metrics\Categorical');
    }

}
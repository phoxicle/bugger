<?php
/**
 * Handles all domain logic related to machine learning/prediction.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */
namespace Risk\Predict;

class Predictor
{
    use \Risk\Stats;

    protected $witness;

	public function __construct()
	{
        $this->witness = new \Risk\Witness();
	}

    /**
     * Predict the bugginess (as a quantile value) for a commit
     * based on a previously-trained model.
     *
     * @param $hash
     */
    public function predict($hash, $keys)
    {
        // Make sure predictive model has already been created

        $cache = new \Risk\Cache();
        if(!$cache->is_valid(\Risk\R\API::get_model_cache_key($keys)))
        {
            $this->witness->log_information('Decision tree not cached. Rebuilding...');
            $trainer = new Risk_Predict_Trainer();
            $trainer->build_decision_tree($keys, 1.0, true);
        }

        // Make sure this is a real commit

        $git = new \Risk\External\Git();
        if(!$git->is_commit_hash_on_remote($hash))
        {
            $this->witness->log_error($hash . ' is not a valid commit hash');
            return;
        }

        // Wrapper model object

        $commit = new \Risk\Model\Commit();
        $commit->set('hash', $hash);

        $value_map = $this->build_value_map_for_commit($commit, $keys);

        $output = \Risk\R\API::predict($value_map);

        $this->witness->log_information($output);
    }

    /**
     * Measure metrics and assign to quantiles.
     *
     * @param \Risk\Model\Commit $commit
     * @return array
     */
    protected function build_value_map_for_commit(\Risk\Model\Commit $commit, $keys)
    {
        $measurer = new \Risk\Metrics\Measurer();
        $correlator = new \Risk\Metrics\Correlator();
        $metrics = new \Risk\Metrics();

        $value_map = [];
        foreach($keys as $key)
        {
            $value = $measurer->measure_metric_for_commit($commit, $key);

            if($value !== null)
            {
                # Place value in quantile if not a categorical metric

                if(!$metrics->is_categorical_metric($key))
                {
                    $boundaries = $correlator->retrieve_cached_boundaries($key);
                    $value = current($correlator->place_in_quantiles_with_boundaries([$value], $boundaries));
                }
            }

            $value_map[$key] = $value;
        }

        return $value_map;
    }


}
<?php
/**
 * Handler for the risk_predict CLI options.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 10/5/13
 */

namespace Risk;

class Predict
{
    protected $witness;

    public function __construct()
    {
        $this->witness = new \Risk\Witness();
    }

    /**
     * Train a model in preparation for prediction. Uses all data as the training set.
     *
     * @param $keys
     */
    public function train(array $keys)
    {
        $this->witness->log_verbose("Building decision tree with all historical data...");

        $trainer = new \Risk\Predict\Trainer();
        $trainer->build_decision_tree($keys, 1.0);
    }

    public function predict_new($hash, $keys)
    {
        $this->witness->log_verbose("Predicting bugginess for commit " . $hash);

        $predictor = new \Risk\Predict\Predictor();
        $predictor->predict($hash, $keys);
    }

    /**
     * Split available data into a training and test set and test the predictive capabilities.
     *
     * @param $keys
     */
    public function test($keys)
    {
        $this->witness->log_verbose("Starting to build decision tree to test with test set...");

        $trainer = new \Risk\Predict\Trainer();
        $trainer->build_decision_tree($keys, 0.9);

        $this->witness->log_verbose('The Confusion matrix has columns for the predicted class, and rows for the actual bugginess class.');
    }


}
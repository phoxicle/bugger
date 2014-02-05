#!/usr/bin/env php
<?php

require_once("../Autoloader.php");

$args = getopt("m:", ['train', 'test', 'new', 'help']);


$metrics = new \Risk\Metrics();

if($args['m'])
{
    if($args['m'] == '[all]')
    {
        $keys = $metrics->get_metric_keys();
    }
    else
    {
        $keys = explode(',', $args['m']);
        if($keys != array_filter($keys)) die('Error: No space allowed between keys' . "\n");
    }
}

if(isset($args['train']))
{
    if($keys)
    {
        $risk = new \Risk\Predict();
        $risk->train($keys);
    }
    else
    {
        $args['help'] = true;
    }
}

if(isset($args['test']))
{
    if($keys)
    {
        $risk = new \Risk\Predict();
        $risk->test($keys);
    }
    else
    {
        $args['help'] = true;
    }
}

if(isset($args['new']))
{
    if($args['hash'] && $keys)
    {
        $risk = new \Risk\Predict();
        $risk->predict_new($args['hash'], $keys);
    }
    else
    {
        $args['help'] = true;
    }
}


if(!array_filter($args) || isset($args['help']))
{

	$keys = $metrics->get_metric_keys();

    echo sprintf("
usage: risk_predict [--test] [--train] [--new --hash a1a1a1] -m=metric_key1,metric_key2

options:
    test                Split available data into training and test sets and calculate predictive performance.
    train               Using all available data, build a predictive model to later be used for prediction.
    new                 Predict the bugginess for the given commit.
    -m                  Possible metric_key values are:");

    foreach($keys as $key) echo sprintf("
                            $key");

    echo sprintf("

                            [all] will select all stored metrics.
    help                Display this menu
\n");
}
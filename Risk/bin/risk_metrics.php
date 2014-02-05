#!/usr/bin/env php
<?php
require_once("../Autoloader.php");

$args = getopt("m:", ['guess','bugginess','help']);

$metrics = new \Risk\Metrics();

if(isset($args['measure']))
{
    if(isset($args['m']))
    {
        $metrics->measure_metric($args['m']);
    }
    else
    {
        $args['help'] = true;
    }
}

if(isset($args['correlate']))
{
    if(isset($args['m']))
    {
        $metrics->correlate($args['m']);
    }
    else
    {
        $args['help'] = true;
    }
}

if(isset($args['plot']))
{
    if(isset($args['m']))
    {
        $metrics->plot($args['m']);
    }
    else
    {
        $args['help'] = true;
    }
}

if(isset($args['compare']))
{
    if(isset($args['m']))
    {
        $metrics->compare_multiple($args['m']);
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
usage: risk_metrics [--measure] [--correlate] [--compare] -m=metric_key

options:
    measure                 Measure a metric for a commit
    correlate               Correlate a metric with commit bugginess
    compare                 Relate a metric with commit bugginess using the multiple comparisons method.
    plot                    Generate a plot of the bugginess vs this metric.
    -m                      metric_key is one of:");

    foreach($keys as $key) echo sprintf("
                                $key");

    echo sprintf("
    help                    Display this menu
\n");
}
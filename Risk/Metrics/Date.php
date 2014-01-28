<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class Date implements IMetric
{

    protected $witness;

    public function __construct()
    {
    }

    public function measure(\Risk\Model\Commit $commit)
    {
        return strtotime($commit->date());
    }

}
<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class DayOfWeek extends Categorical implements IMetric
{

    protected $witness;

    public function __construct()
    {
    }

    public function measure(\Risk\Model\Commit $commit)
    {
        // Use author date since that is when the person actually made the commit

        $git = new \Risk\External\Git();
        $git_commit = $git->get_commit($commit->hash());
        $timestamp = $git_commit->get_author_date();

        return date('D', $timestamp);
    }

}
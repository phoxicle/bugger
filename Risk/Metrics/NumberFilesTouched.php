<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class NumberFilesTouched implements IMetric
{
    protected $witness;
    protected $git;


    public function __construct()
    {
        $this->witness = new \Risk\Witness();
        $this->git = new \Risk\External\Git();
    }

    public function measure(\Risk\Model\Commit $commit)
    {
        $git_commit = $this->git->get_commit($commit->hash());

        return count($git_commit->changed_files());
    }

}
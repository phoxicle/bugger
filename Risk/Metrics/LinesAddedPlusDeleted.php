<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class LinesAddedPlusDeleted implements IMetric
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

        return $git_commit->get_non_test_lines_added() + $git_commit->get_non_test_lines_deleted();
    }

}
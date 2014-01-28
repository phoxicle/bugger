<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class NumberPatchsets implements IMetric
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

        $gerrit_change = new \Risk\External\Change($git_commit->gerrit_change_id());

        return $gerrit_change->patchset_count();
    }

}
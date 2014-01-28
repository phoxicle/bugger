<?php
/**
 * Class to use for metrics which use file content before and/or after a commit.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */
namespace Risk\Metrics;

abstract class AFileBased {

    protected $witness;
    protected $git;

    protected $files_before = [];
    protected $files_after = [];

    public function __construct()
    {
        $this->witness = new \Risk\Witness();
        $this->git = new \Risk\External\Git();
    }

    protected function init_file_before_and_after(\Risk\Model\Commit $commit, $filter = '/.*/')
    {
        $git_commit = $this->git->get_commit($commit->hash());

        $altered_files = $git_commit->changed_files(null, true);
        foreach($altered_files as $altered_file)
        {
            if(!preg_match($filter, $altered_file->name())) continue;

            if($altered_file->is_test_file()) continue;

            try
            {
                $contents_before = $this->git->show_file($commit->hash() . '^', $altered_file->name());
            }
            catch(\Risk\External\Git_Exception $e)
            {
                $contents_before = '';
            }

            try{
                $contents_after = $this->git->show_file($commit->hash(), $altered_file->name());
            }
            catch(\Risk\External\Git_Exception $e)
            {
                continue;
            }

            $this->files_before[$altered_file->name()] = $contents_before;
            $this->files_after[$altered_file->name()] = $contents_after;
        }
    }

}
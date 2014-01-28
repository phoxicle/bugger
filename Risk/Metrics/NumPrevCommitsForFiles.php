<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class NumPrevCommitsForFiles implements IMetric
{

    public function measure(\Risk\Model\Commit $commit)
    {
        $git = new \Risk\External\Git();
        $git_commit = $git->get_commit($commit->hash());

        $num_prev_commits = 0;

        $altered_files = $git_commit->changed_files();
        foreach($altered_files as $altered_file)
        {
            if($altered_file->is_test_file()) continue;

            $location = $commit->hash() . ' -- ' . $altered_file->name();
            $commits = $git->pretty_log($location, "%an");

            $num_prev_commits += count($commits);
        }

        return $num_prev_commits;
    }

}
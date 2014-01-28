<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class NumPrevCommittersForFiles implements IMetric
{

    public function measure(\Risk\Model\Commit $commit)
    {
        // This code overlaps a lot with NumPrevCommitsForFiles, but we want metrics to be autonomous.
        // Could create an abstract class.

        $git = new \Risk\External\Git();
        $git_commit = $git->get_commit($commit->hash());

        $prev_committers = [];

        $altered_files = $git_commit->changed_files();
        foreach($altered_files as $altered_file)
        {
            if($altered_file->is_test_file()) continue;

            $location = $commit->hash() . ' -- ' . $altered_file->name();
            $committers = $git->pretty_log($location, "%an");

            $prev_committers += $committers;
        }

        $num_prev_committers = count(array_unique($prev_committers));

        return $num_prev_committers;
    }

}
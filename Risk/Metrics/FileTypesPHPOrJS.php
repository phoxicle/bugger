<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class FileTypesPHPOrJS extends Categorical implements IMetric
{

    public function measure(\Risk\Model\Commit $commit)
    {
        $git = new \Risk\External\Git();
        $git_commit = $git->get_commit($commit->hash());

        $types = $git_commit->distinct_changed_file_types();

        $types = array_filter($types, function($type){
            return in_array($type, ['php', 'js']);
        });

        if(empty($types)) $types = ['other'];

        sort($types);
        $types_str = implode(',',$types);

        return $types_str;
    }

}
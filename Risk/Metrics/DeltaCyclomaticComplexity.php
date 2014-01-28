<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class DeltaCyclomaticComplexity extends AFileBased implements IMetric
{
    use \Risk\Stats;

    public function measure(\Risk\Model\Commit $commit)
    {
        $this->init_file_before_and_after($commit, '/.*\.php/');

        if(empty($this->files_after)) return;

        $ccn_before = $ccn_after = 0;
        foreach($this->files_before as $file_path => $contents)
        {
            if(empty($contents)) continue;

            try{
                $ccn_before += \Risk\PHPDepend\API::pdepend($contents, 'ccn');
            }
            catch(\Risk\PHPDepend\APIException $e){
                $this->witness->log_error('PHPDepend failed to parse ' . $commit->hash() . '^:' . $file_path);
            }
        }

        foreach($this->files_after as $file_path => $contents)
        {
            if(empty($contents)) continue;

            try{
                $ccn_after += \Risk\PHPDepend\API::pdepend($contents, 'ccn');
            }
            catch(\Risk\PHPDepend\APIException $e){
                $this->witness->log_error('PHPDepend failed to parse ' . $commit->hash() . ':' . $file_path);
            }
        }

        return $ccn_after - $ccn_before;
    }

}

<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/16/13
 */
namespace Risk\Metrics;

class MethodLOC extends AMethodBased implements IMetric
{
    use \Risk\Stats;

    public function measure(\Risk\Model\Commit $commit)
    {
        $contents_to_measure = $this->get_contents_to_measure_for_commit($commit);

        if($contents_to_measure)
        {
            $this->witness->log_information("Measuring content");
            $loc = \Risk\PHPDepend\API::pdepend($contents_to_measure, 'lloc'); // Logical lines of code

            return $loc;
        }

    }

}

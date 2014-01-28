<?php
/**
 * Interface required for all metrics.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/5/13
 */
namespace Risk\Metrics;
interface IMetric {

    public function measure(\Risk\Model\Commit $commit);

}
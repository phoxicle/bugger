<?php
namespace Risk\Model;
class BugginessMetricsView extends Model
{
	protected static $columns = array(
        'commit_id',
        'bugginess',
        'key',
        'value'
	);

	protected static $table = 'risk_v_bugginess_metrics';
	protected static $conf_setting = 'devtools_database';
}

 /*

 CREATE VIEW risk_v_bugginess_metrics AS (
    SELECT commit_id, bugginess, `key`, `value` FROM
        risk_commits
    JOIN
        risk_metrics
    ON risk_commits.id = risk_metrics.commit_id
 );

 */
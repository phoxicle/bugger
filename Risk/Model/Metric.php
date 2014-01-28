<?php

namespace Risk\Model;

class Metric extends Model
{
	protected static $columns = array(
		'id',
        'commit_id',
        'key',
        'value'
	);

	protected static $table = 'risk_metrics';
	protected static $conf_setting = 'devtools_database';
}

 /*

 CREATE TABLE `risk_metrics` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `commit_id` INT NOT NULL,
    `key` VARCHAR(30) NOT NULL,
    `value` VARCHAR(30) NOT NULL,

    PRIMARY KEY(id),
    CONSTRAINT uc_commit_id_key UNIQUE(commit_id, `key`),
    FOREIGN KEY(commit_id) REFERENCES risk_commits(id)
) ENGINE=InnoDB CHARACTER SET=utf8;

 CREATE INDEX idx_risk_metrics_key ON risk_metrics (`key`);
 
 LOAD DATA LOCAL INFILE 'risk_metrics.csv' INTO TABLE `risk_metrics` FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n' (id,commit_id,`key`,`value`);

 */
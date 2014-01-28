<?php
namespace Risk\Model;

class Ticket extends Model
{
    protected static $SLA = [
        'Blocker' => 1,
        'Critical' => 3,
        'Major' => 60,
        'Minor' => 120
    ];

	protected static $columns = array(
		'id',
		'jira_id',
		'is_bug',
		'priority',
		'report_date',
        'fixing_commit_id',
        'is_processed_by_diff',
        'is_processed_by_bisect',

	);

	protected static $table = 'risk_tickets';
	protected static $conf_setting = 'devtools_database';


    public function sla()
    {
        return \Risk\Model\Ticket::$SLA[$this->priority()];
    }

    public function is_revert()
    {
        return strpos($this->jira_id(), 'REVERT') !== false;
    }
}


 /*

 CREATE TABLE `risk_tickets` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `jira_id`  VARCHAR(255) NOT NULL,
    `is_bug` BIT NOT NULL,
    `priority` VARCHAR(10),
    `report_date` datetime NOT NULL,
    `fixing_commit_id` INT,
    `is_processed_by_diff` BIT NOT NULL,
    `is_processed_by_bisect` BIT NOT NULL,

    PRIMARY KEY(id),
    UNIQUE(jira_id),
    FOREIGN KEY(fixing_commit_id) REFERENCES risk_commits(id)
) ENGINE=InnoDB CHARACTER SET=utf8;


LOAD DATA LOCAL INFILE 'risk_tickets.csv' INTO TABLE `risk_tickets` FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n' (id,jira_id,is_bug,priority,report_date,fixing_commit_id,is_processed_by_diff,is_processed_by_bisect);


 SET FOREIGN_KEY_CHECKS=0;
  TRUNCATE risk_tickets;
  TRUNCATE risk_commits;
  TRUNCATE risk_clues;




 */
<?php
namespace Risk\Model;

class Clue extends Model
{
	protected static $columns = array(
		'id',
        'commit_id',
        'ticket_id',
        'determined_by'
	);

	protected static $table = 'risk_clues';
	protected static $conf_setting = 'devtools_database';
}

 /*

 CREATE TABLE `risk_clues` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `commit_id` INT NOT NULL,
    `ticket_id`  INT NOT NULL,
    `determined_by` TINYINT NOT NULL,

    PRIMARY KEY(id),
    FOREIGN KEY(commit_id) REFERENCES risk_commits(id),
    FOREIGN KEY(ticket_id) REFERENCES risk_tickets(id)
) ENGINE=InnoDB CHARACTER SET=utf8;

 CREATE INDEX idx_determined_by ON risk_clues (determined_by);
 
 LOAD DATA LOCAL INFILE 'risk_clues.csv' INTO TABLE `risk_clues` FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n' (id,commit_id,ticket_id,determined_by);

 */
<?php
namespace Risk\Model;

class Commit extends Model
{
	protected static $columns = array(
		'id',
		'hash',
        'date',
        'is_revert',
        'bugginess'
	);

	protected static $table = 'risk_commits';
}

//TODO fix importing date from CSV

 /*


 CREATE TABLE `risk_commits` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `hash` varchar(40) NOT NULL,
   `date` datetime NOT NULL,
   `is_revert` BIT NOT NULL,
   `bugginess` FLOAT DEFAULT -1,

   PRIMARY KEY (`id`),
   UNIQUE KEY `hash` (`hash`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8

LOAD DATA LOCAL INFILE 'risk_commits.csv' INTO TABLE `risk_commits` FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n' (id,hash,date,is_revert,bugginess,@dummy);

 */

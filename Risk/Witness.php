<?php
/**
 * Handles interface to \Risk\External\Git.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 14/12/13
 */

namespace Risk;

class Witness
{
	CONST VERBOSE = 0;
	CONST INFO = 1;
	CONST WARNING = 2;
	CONST ERROR = 3;
	
	public function __construct(){
		//TODO implement level
	}
	
	public function log_verbose($string){
		echo 'VERBOSE: ' . $string . PHP_EOL;
	}
	public function log_information($string){
		echo 'INFO: ' . $string . PHP_EOL;
	}
	public function log_warning($string){
		echo 'WARNING: ' . $string . PHP_EOL;
	}
	public function log_error($string){
		echo 'ERROR: ' . $string . PHP_EOL;
	}

	public static function add_log_file_appender($log_file){
		//TODO implement
	}
}
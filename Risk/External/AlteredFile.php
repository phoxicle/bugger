<?php
/**
 * Handles interface to altered files of commits.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 14/12/13
 */
namespace Risk\External;

class AlteredFile
{
	public function is_test_file();
	public function deleted_lines();
	public function get_added_line_numbers();
	public function name();	
	public function commit();
	
}
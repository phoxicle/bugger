<?php
/**
 * Handles interface to Git.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 14/12/13
 */

namespace Risk\External;

class Git
{
	public function all_commits_between($start_date, $end_date);
	public function get_commit($hash);
	public function is_commit_hash_on_remote($hash);
	public function blame($filename, $line_number, $options, $hash);
	public function get_long_hash($short_hash);
	public function pretty_log($location, $format);
	public function show_file($hash, $filename);
}
<?php
/**
 * Handles interface to Git Commits.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 14/12/13
 */
namespace Risk\External;

class Commit
{
	public function hash();
	public function date();
	public function tickets();
	public function short_hash();
	public function subject();
	public function is_revert();
	public function get_author_date();
	public function changed_files($filter);
	public function gerrit_change();
	public function get_commit_date();
	public function gerrit_change_id();
	public function distinct_changed_file_types();
	public function get_non_test_lines_added();
	public function get_non_test_lines_deleted();
	public function show($filename);
}
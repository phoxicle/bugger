<?php 
/**
 * Handles interface to Gerrit Changes.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 14/12/13
 */

namespace Risk\External;

class Change
{
	public function upvoters();
	public function patchset_count();
}
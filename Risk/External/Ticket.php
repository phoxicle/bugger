<?php
/**
 * Handles interface to Jira Tickets.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 14/12/13
 */
namespace Risk\External;

class Ticket
{
	public function ticket_number();
	public function is_exempt();
	public function status();
	public function is_revert();
	public function is_bug();
	public function priority();
	public function created();
}
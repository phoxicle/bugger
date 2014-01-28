<?php
/**
 * Handler for the risk_mine CLI options.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/5/13
 */
namespace Risk;

class Mine
{
    use \Risk\Stats;

	protected $witness;
    protected $git;

    public static $start_date = '2012-06-01';
    public static $end_date ='2013-09-01';

	/**
	 *
	 */
	public function __construct()
	{
        $this->witness = new \Risk\Witness();

        $this->git = new \Risk\External\Git();

        \Risk\Witness::$APPENDERS = [];

        $this->witness->log_information('Starting risk...');
        $this->witness->log_information('Time is ' . date('r'));
	}

	/**
	 * Executes the code to retrieve jira tickets and their associated commits.
	 *
	 */
	public function retrieve()
	{
        $log_file = Risk::log_path() . 'retrieve_log.txt';
        \Risk\Witness::add_log_file_appender($log_file);

        $this->witness->log_information('Retrieving...');

        $start_time = time();

        $retriever = new \Risk\Mine_Retriever();
        $retriever->retrieve_commits_and_tickets(self::$start_date,self::$end_date);

        self::timing('retrieval_time', time() - $start_time);

        $this->dump_stats();
    }

    /**
     * Executes code to mine bug-introducing commits for every bug-type issue saved
     * previously from $this->retrieve()
     */
    public function guess()
    {
        $log_file = Risk::log_path() . 'guess_log.txt';
        \Risk\Witness::add_log_file_appender($log_file);

        $this->witness->log_information('Guessing...');

        // DIFF method
        $start_time = time();

        $guesser = new \Risk\Mine\Guesser();
        $guesser->find_clues();

        self::timing('guesser_time', time() - $start_time);

        $this->dump_stats();
    }

    /**
     * Extract and save the bisected commit for every bug-type ticket.
     * Should be run after retrieve().
     */
    public function bisect()
    {
        $log_file = Risk::log_path() . 'bisect_log.txt';
        \Risk\Witness::add_log_file_appender($log_file);

        $this->witness->log_information('Bisecting...');

        // git-bisect method
        $start_time = time();

        $bisect_finder = new \Risk\Mine\BisectFinder();
        $bisect_finder->find_clues();

        self::timing('bisect_finder_time', time() - $start_time);

        $this->dump_stats();
    }

    /**
     * Compute and save a bugginess score for every commit in our DB.
     * Should be run after retrieve(), guess(), and bisect() have been run.
     */
    public function bugginess()
    {
        $log_file = Risk::log_path() . 'bugginess_log.txt';
        \Risk\Witness::add_log_file_appender($log_file);

        $this->witness->log_information('Calculating bugginess...');
        $start_time = time();

        $bisect_finder = new \Risk\Mine\BugginessCalculator();
        $bisect_finder->assign_bugginess_to_all_commits();

        self::timing('bugginess_calculator_time', time() - $start_time);

        $this->dump_stats();
    }

}

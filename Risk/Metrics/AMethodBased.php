<?php
/**
 * Class to use for metrics which should be measured according to the methods touched by the commit.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */
namespace Risk\Metrics;

//TODO add php parser lib
//require_once(dirname(__DIR__).'/../../'.'lib/vendor/PHP-Parser-master/lib/bootstrap.php');

abstract class AMethodBased {

    protected $witness;
    protected $git;

    public function __construct()
    {
        $this->witness = new \Risk\Witness();
        $this->git = new \Risk\External\Git();
    }

    /**
     * Given a commit, get all the PHP methods touched appended together and wrapped in a class.
     * TODO not tested :(
     *
     * @param \Risk\Model\Commit $commit
     * @return string
     */
    protected function get_contents_to_measure_for_commit(\Risk\Model\Commit $commit)
    {
        $git_commit = $this->git->get_commit($commit->hash());

        $altered_files = $git_commit->changed_files('/.*\.php$/', true);

        $methods_to_measure = [];
        foreach($altered_files as $altered_file)
        {
            if($altered_file->is_test_file()) continue;

            $method_contents = $this->get_altered_method_contents_in_altered_file($altered_file);
            if($method_contents) $methods_to_measure[] = $method_contents;
        }

        if($methods_to_measure)
        {
            $contents_to_measure = implode("\n", $methods_to_measure);
            // class and parent needed to to make self/parent keywords work.
            return "<?php class foo extends bar {\n$contents_to_measure\n}";
        }
    }

    /**
     * Get the contents of the methods touched in a \Risk\External\AlteredFile.
     *
     * @param \Risk\External\AlteredFile $altered_file
     * @return string
     */
    protected function get_altered_method_contents_in_altered_file(\Risk\External\AlteredFile $altered_file)
    {
        $added_line_numbers = $altered_file->get_added_line_numbers();

        if(empty($added_line_numbers)) return;

        $git_commit = $altered_file->commit();
        $file_contents = $git_commit->show($altered_file->name());

        $altered_method_lines = $this->determine_method_lines_touched_by_lines($file_contents, $added_line_numbers);

        $this->witness->log_verbose('Found ' . count($altered_method_lines) . ' altered methods in file ' . $altered_file->name());

        $altered_methods = [];
        foreach($altered_method_lines as $line_range)
        {
            list($start, $end) = $line_range;
            $altered_methods[] = $this->get_file_contents_in_line_range($file_contents, $start, $end);
        }

        if($altered_methods)
        {
            return implode("\n", $altered_methods);
        }
    }

    /**
     * Extract the content of a file in the provided line range.
     *
     * @param $file_contents
     * @param $start
     * @param $end
     * @return string
     */
    protected function get_file_contents_in_line_range($file_contents, $start, $end)
    {
        $file_lines = preg_split("/((\r?\n)|(\r\n?))/", $file_contents);

        $file_lines_in_range = array_slice($file_lines, $start-1, $end-$start+1);

        return implode("\n", $file_lines_in_range);
    }

    /**
     * Given an array of line numbers in a file, determine the methods containing all of those lines.
     *
     * @param $file_contents
     * @param $line_numbers
     * @return array of altered method lines, in the form [ [method_start_line, method_end_line], [...]]
     */
    protected function determine_method_lines_touched_by_lines($file_contents, $line_numbers)
    {
        // TODO add PHPParser libraries and then correct classnames
        
    	$parser = new PHPParser_Parser(new PHPParser_Lexer);

        try{
            $stmts = $parser->parse($file_contents);
        }
        catch(PHPParser_Error $e)
        {
            $this->witness->log_error('Error parsing contents');
            return [];
        }

        // We take the first class we find
        foreach($stmts as $stmt)
        {
            if($stmt instanceof PHPParser_Node_Stmt_Class)
            {
                $class = $stmt;
                break;
            }
        }

        if(!$class) return [];

        $methods = $class->getMethods();

        $altered_method_lines = [];

        foreach($methods as $method)
        {
            $start = $method->getAttribute('startLine');
            $end = $method->getAttribute('endLine');

            foreach($line_numbers as $line_number)
            {
                if($line_number < $start)
                {
                    array_shift($line_numbers);
                }
            }

            $lines_in_this_method = [];
            foreach($line_numbers as $line_number)
            {
                if($line_number <= $end)
                {
                    $lines_in_this_method[] = $line_number;
                    array_shift($line_numbers);
                }
            }

            if($lines_in_this_method)
            {
                $altered_method_lines[] = [$start, $end];
            }
        }

        return $altered_method_lines;
    }
}
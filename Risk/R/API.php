<?php
/**
 * Interface between PHP and R's command line utility, Rscript.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/5/13
 */

namespace Risk\R;

/*
 * install.packages("rjson")
 */
class API
{
    CONST MODEL_CACHE_KEY = 'tree_model';

    /**
     * Calculate the pearson, kendall, and spearman correlation coefficients between columns.
     *
     * @param $column1
     * @param $column2
     * @return string
     * @throws \Risk\R\APIException
     */
    public static function correlation($column1, $column2)
    {
        if(count($column1) != count($column2))
        {
            throw new \Risk\R\APIException('Columns must be same length');
        }

        $output = self::execute_command('cor.r', [$column1, $column2] );

        return $output;
    }

    /**
     * Correlate two columns using the multiple comparisons method.
     *
     * @param $column1 Numerical values
     * @param $categories Values which will be put into partitions
     * @return string
     */
    public static function multiple_comparisons($column1, $categories)
    {
        foreach ($categories as &$category) $category = '"'.$category.'"';

        $output = self::execute_command('multcomp.r', [$column1, $categories] );

        return $output;
    }

    /**
     * Perform the npartest, which handles the case with binary metric values
     * (i.e. have only two values).
     *
     * @param $column1
     * @param $categories
     * @return string
     */
    public static function npartest($column1, $categories)
    {
        foreach ($categories as &$category) $category = '"'.$category.'"';

        $output = self::execute_command('npartest.r', [$column1, $categories] );

        return $output;
    }

    /**
     * Perform the normality test.
     *
     * @param $column
     * @return string
     */
    public static function normality($column)
    {
        $output = self::execute_command('normality.r', [$column] );

        return $output;
    }

    /**
     *  For four partitions, returns array of numbers of the form
     * [min, 25th percentile, 50th percentile, 75th percentile, max]
     *
     * @param $values
     * @param $num_partitions
     * @return array
     */
    public static function quantile($values, $num_partitions)
    {
        $prob_interval = 1 / $num_partitions;
        $probs = [];
        for($i=0; $i <= $num_partitions; $i++)
        {
            $probs[] = $i * $prob_interval;
        }

        $output = self::execute_command('quantile.r', [$values, $probs]);

        list(,$values_as_string) = preg_split('/\r\n|\r|\n/',$output);
        $values = preg_split('/\s+/',$values_as_string);

        $quantile_values = array_filter($values, function($v){ return $v !== ''; });

        return $quantile_values;
    }

    public static function plot($column1, $column2, $key)
    {
        $key = '"' . $key . '"';

        // $column 1 on y axis
        $output = self::execute_command('plot.r', [$column1, $column2, $key] );
        list(,$file_path) = explode('[1]', $output);
        return trim($file_path);
    }

    /**
     * @param $keys The metric keys
     * @param $values of the form [ ['bugginess' => 1, 'metric1' => 2, ...], ... ]
     * @param $training_split
     */
    public static function decision_tree($keys, $values, $training_split)
    {
        if(count($values) < 1) return;

        // Save the values to a file, since it's too large for cmd line args
        $file_path = sys_get_temp_dir() . '/Risk_predict_values.csv';

        $fh = fopen($file_path, 'w');

        $header = array_keys($values[0]);
        fputcsv($fh, $header);

        foreach($values as $map)
        {
            $fields = array_values($map);
            fputcsv($fh, $fields);
        }

        fclose($fh);

        $file_path = '"' . $file_path . '"';

        $cache = new \Risk\Cache();
        $cache_path = '"' . $cache->full_cache_path(self::get_model_cache_key($keys)) . '"';

        $output = self::execute_command('tree.r', [$file_path, $training_split, $cache_path] );

        return $output;
    }

    public static function get_model_cache_key($keys)
    {
        sort($keys);
        return self::MODEL_CACHE_KEY . '_' . md5(implode('-', $keys));
    }

    /**
     * Predict the bugginess quantile for the new values.
     *
     * @param $new_value_map
     * @return string
     */
    public static function predict($new_value_map)
    {
        $keys = array_keys($new_value_map);
        $values = array_values($new_value_map);

        $cache_key = self::get_model_cache_key($keys);

        foreach($keys as &$v) $v = '"' . $v . '"';
        foreach($values as &$v) $v = '"' . $v . '"';

        $cache = new \Risk\Cache();
        $cache_path = '"' . $cache->full_cache_path($cache_key) . '"';

        $output = self::execute_command('predict.r', [$keys, $values, $cache_path] );

        return $output;
    }

    /**
     * Run an Rscript file and return the full output.
     *
     * @param $script name of the script to run (must be in this directory)
     * @param $args array of arguments to pass to R
     * @return string
     */
    protected static function execute_command($script, $args)
    {
        $r_dir = dirname(__DIR__) . '/R/';

        $file_path = $r_dir . $script;

        // Always add R directory as extra argument in case script needs it to include other scripts.

        $args[] = '"'.$r_dir.'"';
        $json_args = array_map('json_encode', $args);

        $cmd = $file_path . ' --args ' . implode(' ', $json_args);

        $output = shell_exec($cmd);

        return $output;
    }

}
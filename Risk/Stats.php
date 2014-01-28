<?php
/**
 * Some nice functions for recording various continuous metrics in a log file.
 *
 * Useful for when you want some stats at the end of a script's run.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 8/27/13
 */

namespace Risk;

trait Stats
{
    public static $stats = [];

    protected static function space()
    {
        return str_replace('_','.',get_class()) . '.';
    }

    public static function increment($key)
    {
        $key = self::space() . $key;

        if(!isset(\Risk\Stats::$stats[$key])) \Risk\Stats::$stats[$key] = 0;
        \Risk\Stats::$stats[$key]++;
    }

    public static function timing($key, $value)
    {
        $key = self::space() . $key;
        if(!isset(\Risk\Stats::$stats[$key])) \Risk\Stats::$stats[$key] = 0;
        \Risk\Stats::$stats[$key] = $value;
    }

    public function dump_stats()
    {
        $witness = new \Risk\Witness();
        $witness->log_warning("RISK STATS DUMP");

        // Log to witness, since that has a log file
        foreach(\Risk\Stats::$stats as $key => $value)
        {
            $witness->log_warning($key . ' : ' . $value);
        }

        \Risk\Stats::$stats = [];
    }
}
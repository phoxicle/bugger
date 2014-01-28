<?php
/**
 * Performs file-based caching.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/5/13
 */

namespace Risk;

class Cache
{
    protected $witness;

    CONST PREFIX = 'risk_cache_';

    public function __construct()
    {
        $this->witness = new \Risk\Witness();
    }

    public function is_valid($key)
    {
        $file_name = self::full_cache_path($key);
        return file_exists($file_name);
    }

    public function retrieve($key)
    {
        $contents = file_get_contents(self::full_cache_path($key));
        if($contents) return json_decode($contents);
    }

    public function cache($key, $data)
    {
        $this->witness->log_verbose('Caching ' . $key);
        file_put_contents(self::full_cache_path($key), json_encode($data));
    }

    public function full_cache_path($key)
    {
        return self::PREFIX . $key;
    }

}
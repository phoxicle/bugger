<?php
/**
 * Provides an interface from PHP to the PHPDepend binary.
 *
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */
namespace Risk\PHPDepend;

class API {

    /**
     * Run the pdepend command for some code and metric
     * @param $contents
     * @param $metric
     * @return int
     */
    public static function pdepend($contents, $metric)
    {
        $in_file_path = sys_get_temp_dir() . '/Risk_CC_in';
        file_put_contents($in_file_path, $contents);

        $out_file_path = sys_get_temp_dir() . '/Risk_CC_out';
        $cmd = static::pdepend_path() . ' --configuration=' . static::config_path()
                . ' --summary-xml=' . $out_file_path . ' ' . $in_file_path;
        exec($cmd);

        $result_xml_str = file_get_contents($out_file_path);
        $result_xml = new SimpleXMLElement($result_xml_str);

        // If there is no files node, then the contents were invalid
        if($result_xml->files)
        {
            return (int)$result_xml[$metric];
        }
        else
        {
            throw new \Risk\PHPDepend\APIException('Invalid contents for pdepend', $contents);
        }
    }

    protected static function config_path()
    {
        return dirname(__DIR__) . '/PHPDepend/config.xml';
    }

    protected static function pdepend_path()
    {
        return dirname(dirname(__DIR__)) . '/vendor/pdepend.phar';
    }

}
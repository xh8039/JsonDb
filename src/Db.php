<?php

/**
 * @package JsonDb
 * @author  易航
 * @version 2.4
 * @link    https://gitee.com/yh_IT/json-db
 *
 **/

namespace JsonDb\JsonDb;

class Db
{
    static private $options = [];
    static public function setConfig($options)
    {
        self::$options = $options;
    }
    static public function table($table_name)
    {
        return (new JsonDb(self::$options))->table($table_name);
    }
    static public function name($table_name)
    {
        if (self::$options['prefix']) {
            $table_name = self::$options['prefix'] . $table_name;
        }
        return (new JsonDb(self::$options))->table($table_name);
    }
}

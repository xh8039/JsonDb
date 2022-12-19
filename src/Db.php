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

	/**
	 * 初始化配置参数
	 * @access public
	 * @param array $options 连接配置
	 * @return void
	 */
	static public function setConfig($options)
	{
		self::$options = $options;
	}

	/**
	 * 指定当前操作的数据表(不带前缀)
	 * @access public
	 * @param string $table 表名
	 * @return JsonDb
	 */
	static public function table($table_name)
	{
		return (new JsonDb(self::$options))->table($table_name);
	}

	/**
	 * 指定当前操作的数据表
	 * @access public
	 * @param string $table 表名
	 * @return JsonDb
	 */
	static public function name($table_name)
	{
		if (self::$options['prefix']) {
			$table_name = self::$options['prefix'] . $table_name;
		}
		return (new JsonDb(self::$options))->table($table_name);
	}
}
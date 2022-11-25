<?php

/**
 * @package JsonDb
 * @author  易航
 * @version 2.3
 * @link    https://gitee.com/yh_IT/json-db
 *
 **/

namespace JsonDb\JsonDb;

class JsonDb
{

	/** 自定义配置项 */
	public $options = [
		'table_name' => null, //单表模式
		'encode' => null, //加密函数
		'decode' => null, //解密函数
		'file_suffix' => '.json', //文件后缀名
		'path' => null, //自定义存储路径
		'directory_name' => 'JsonDb', //存储的目录名
		'debug' => true, //调试模式
	];

	/** 错误信息 */
	public $error;

	/** JSON数据存储文件夹基于的根目录 */
	public $dataRoot;

	/** JSON数据存储文件夹的根目录 */
	public $tableRoot;

	/** JSON数据表的文件路径 */
	public $tableFile;

	/** 筛选后的结果 */
	public $filterResult;

	/** 对数据限制的处理条数 */
	public $limit = null;

	/**
	 * 初始化配置
	 * @param array $options JsonDb配置
	 */
	public function __construct($options = null)
	{
		// 更新配置数据
		$this->options = $options ? array_merge($this->options, $options) : $this->options;

		// 检测站点根目录
		if (@$_SERVER['DOCUMENT_ROOT']) {
			$this->dataRoot = $_SERVER['DOCUMENT_ROOT'] . '/';
		} else {
			$this->dataRoot = './';
		}

		// 数据存储的目录
		$this->tableRoot = $this->dataRoot . $this->options['path'] . ($this->options['path'] ? '/' : null) . $this->options['directory_name'];

		// 单表模式
		$this->options['table_name'] ? $this->table($this->options['table_name']) : false;
	}

	/**
	 * 添加单条数据
	 * @access public
	 * @param array $array 要添加的数据
	 * @return integer|false 成功则以int形式返回添加数据的总字节 失败则返回false
	 */
	public function insert(array $array)
	{
		// 获取表中原来的数据
		$data = $this->jsonFile();
		$end_data = end($data);
		$array['id'] = @$array['id'] ? $array['id'] : (is_numeric(@$end_data['id']) ? $end_data['id'] + 1 : 1);
		array_push($data, $array);
		return $this->arrayFile($data);
	}

	/**
	 * 批量添加数据
	 * @access public
	 * @param array $array 数据集
	 * @return integer 返回共添加数据的条数
	 */
	public function insertAll(array $array)
	{
		$insertAll = 0;
		foreach ($array as $value) {
			$insertAll++;
			if ($insertAll === $this->limit) {
				break;
			}
			$this->insert($value);
		}
		return $insertAll;
	}

	/**
	 * 更新数据
	 * @access public
	 * @param array $array 要更新的数据
	 * @return integer 返回更新的键值数量
	 */
	public function update(array $array)
	{
		$file = $this->jsonFile();
		$update = 0;
		if (empty($this->filterResult)) {
			return 0;
		}
		$where = $this->filterResult;
		foreach ($where as $key => $value) {
			foreach ($array as $array_key => $array_value) {
				$update++;
				$file[$key][$array_key] = $array_value;
				if ($update == $this->limit) {
					break;
				}
			}
		}
		$this->arrayFile($file);
		$this->filterResult = false;
		return $update;
	}

	/**
	 * 删除部分数据
	 * @access public
	 * @param array $array 要删除的部分数据字段名
	 * @return integer  返回影响数据的键值数量
	 */
	public function delete(array $array)
	{
		$file = $this->jsonFile();
		$delete = 0;
		$where = $this->filterResult;
		foreach ($where as $key => $value) {
			foreach ($array as $array_value) {
				$delete++;
				unset($file[$key][$array_value]);
				if ($delete == $this->limit) {
					break;
				}
			}
		}
		$this->arrayFile($file);
		$this->filterResult = false;
		return $delete;
	}

	/**
	 * 删除指定字段所有数据
	 * @access public
	 * @param bool $type 删除整个表时使用布尔值true 否则留空
	 * @return integer 返回影响数据的条数
	 */
	public function deleteAll($type = false)
	{
		if ($type === true) {
			return unlink($this->tableFile);
		}
		$file = $this->jsonFile();
		$delete = 0;
		$where = $this->filterResult;
		foreach ($where as $key => $value) {
			$delete++;
			unset($file[$key]);
			if ($delete == $this->limit) {
				break;
			}
		}
		$this->arrayFile($file);
		$this->filterResult = false;
		return $delete;
	}

	/**
	 * 查询单条数据
	 * @access public
	 * @return array
	 */
	public function find()
	{
		$where = $this->filterResult;
		$this->filterResult = false;
		if (empty($where)) {
			return [];
		}
		return current($where);
	}

	/**
	 * 查询多条数据
	 * @access public
	 * @return array
	 */
	public function select()
	{
		$where = $this->filterResult;
		$this->filterResult = false;
		if (empty($where)) {
			return [];
		}
		return array_values($where);
	}

	/**
	 * 查询所有数据
	 * @access public
	 * @return array
	 */
	public function selectAll()
	{
		$data = $this->jsonFile();
		if (empty($data)) {
			return [];
		}
		if (count($data) == 1) {
			$data = $data[0];
			return $data;
		}
		return array_values($data);
	}

	/**
	 * 查询数据的长度
	 * @access public
	 * @return integer
	 */
	public function count()
	{
		$where = $this->filterResult;
		$this->filterResult = false;
		$data = $where ? $where : $this->jsonFile();
		if (empty($data)) {
			return 0;
		}
		return count($data);
	}

	/**
	 * 指定查询数量
	 * @access public
	 * @param int $offset 起始位置
	 * @param int $length 查询数量
	 * @return $this
	 */
	public function limit(int $offset, int $length = null)
	{
		$this->limit = $offset;
		$file = $this->filterResult ? $this->filterResult : $this->jsonFile();;
		if (empty($file)) {
			$this->DbError('limit语句查找不到数据');
			return $this;
		}
		$file = array_values($file);
		$data = [];
		if (is_null($length)) {
			$length = count($file);
		} else {
			$length = $offset + $length;
		}
		foreach ($file as $key => $value) {
			if ($key >= $offset && $key < $length) {
				$data[$key] = $value;
			}
		}
		$this->filterResult = $data;
		return $this;
	}

	/**
	 * 指定当前操作的数据表
	 * @access public
	 * @param string $table_name 表名
	 * @return $this
	 */
	public function table($table_name)
	{
		$this->tableFile = $this->tableRoot . '/' . $table_name . $this->options['file_suffix'];
		$this->tableName = $table_name;
		return $this;
	}

	/**
	 * 内部调用表切换
	 * @access public
	 * @param string $table_name 要切换的数据表名
	 * @return $this
	 */
	private function tableSwitch($table_name)
	{
		$this->tableFile = $this->tableRoot . '/' . $table_name . $this->options['file_suffix'];
		return $this;
	}

	/**
	 * 检查指定数据表是否存在
	 * @access public
	 * @param string $table_name 可选 数据表名
	 * @return bool
	 */
	public function tableExists($table_name = null)
	{
		if ($table_name) {
			$tableFile = $this->tableRoot . '/' . $table_name . $this->options['file_suffix'];
		} else {
			$tableFile = $this->tableFile;
		}
		if (file_exists($tableFile)) {
			return true;
		}
		return false;
	}

	/**
	 * 根据字段条件过滤数组中的元素
	 * @access public
	 * @param string $field_name 字段名
	 * @param mixed  $operator 操作符 默认为 ==
	 * @param mixed  $field_value 字段值
	 * @return $this
	 */
	public function where($field_name, $operator = null, $field_value = null)
	{
		$file = $this->filterResult ? $this->filterResult : $this->jsonFile();
		if (!is_array($file)) {
			$this->filterResult = [];
			return $this;
		}
		$param = func_num_args();
		if ($param == 1) {
			$match = preg_match_all('/`field_([\w,\d]+)`/s', $field_name, $match_array);
			if (!$match) {
				$this->DbError('判断条件无效 请检查是否存在伪字段名：`field_字段名`');
				return $this;
			}
			foreach ($match_array[1] as $key => $value) {
				$match_array[1][$key] = '$value[\'' . $value . '\']';
			}
			$str = str_replace($match_array[0], $match_array[1], $field_name);
			$str = str_replace('`', '\'', $str);
			$str = 'return(' . $str . ');';
			foreach ($file as $key => $value) {
				$result = eval($str);
				if (!$result) {
					unset($file[$key]);
				}
			}
		}
		if ($param == 2) {
			$field_value  = $operator;
			foreach ($file as $key => $value) {
				if (@$value[$field_name] != $field_value) {
					unset($file[$key]);
				}
			}
		}
		if ($param == 3) {
			$operator == '=' ? $operator = '==' : $operator = $operator;
			foreach ($file as $key => $value) {
				$str = 'return ' . $value[$field_name] . ' ' . $operator . ' ' . $field_value . ';';
				$result = eval($str);
				if (!$result) {
					unset($file[$key]);
				}
			}
		}
		$this->filterResult = $file;
		return $this;
	}

	public function whereAll(array $array)
	{
		$value_array = false;
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$value_array = true;
			}
		}
		if ($value_array) {
			foreach ($array as $key => $value) {
				$this->where($value[0], $value[1], $value[2]);
			}
		} else {
			foreach ($array as $key => $value) {
				$this->where($key, $value);
			}
		}
		return $this;
	}

	/**
	 * LIKE查询
	 * @access public
	 * @param string $field_name 字段名
	 * @param mixed $field_value 字段值
	 * @return $this
	 */
	public function whereLike($field_name, $field_value)
	{
		$file = $this->filterResult ? $this->filterResult : $this->jsonFile();
		$field_value = preg_quote($field_value, '/');
		if (preg_match('/%.*%/', $field_value) <= 0) {
			if (preg_match('/^%/', $field_value) > 0) {
				$field_value .= '$';
			}
			if (preg_match('/%$/', $field_value)) {
				$field_value = '^' . $field_value;
			}
		}
		$field_value = str_replace('%', '.*', $field_value);
		$field_value = '/' . $field_value . '/s';
		foreach ($file as $key => $value) {
			if (preg_match($field_value, @$value[$field_name]) > 0) {
			} else {
				unset($file[$key]);
			}
		}
		$this->filterResult = $file;
		return $this;
	}

	/**
	 * 查询指定键名之前的数据
	 * @access public
	 * @param string $field_name 字段名
	 * @return $this
	 */
	public function beforeKey($field_name)
	{
		$file = $this->filterResult ? $this->filterResult : $this->jsonFile();
		$keys = array_keys($file);
		$len = array_search($field_name, $keys);
		$this->filterResult = array_slice($file, 0, $len);
		return $this;
	}

	/**
	 * 查询指定键名之后的数据
	 * @access public
	 * @param string $field_name 字段名
	 * @return $this
	 */
	public function afterKey($field_name)
	{
		$file = $this->filterResult ? $this->filterResult : $this->jsonFile();
		$keys = array_keys($file);
		$offset = array_search($field_name, $keys);
		$this->filterResult = array_slice($file, $offset + 1);
		return $this;
	}

	/**
	 * ORDER排序
	 * @access public
	 * @param string $field_name 字段名
	 * @param SORT_ASC|SORT_DESC $order 排序方式：SORT_ASC - 按升序排列|SORT_DESC - 按降序排列
	 * @return $this
	 */
	public function order($field_name, $order = SORT_DESC)
	{
		if (empty($this->filterResult)) {
			$file = $this->jsonFile();
		} else {
			$file = $this->filterResult;
		}
		foreach ($file as $key => $value) {
			if (!isset($value[$field_name])) {
				$file[$key][$field_name] = ($order == SORT_DESC ? 0 : 99999999);
			}
		}
		$column = array_column($file, $field_name);
		array_multisort($column, $order, $file);
		$this->filterResult = $file;
		return $this;
	}

	/**
	 * 数组转JSON数据
	 * @access public
	 * @param array $array 要转换的数组
	 * @return json|string
	 */
	public function jsonEncode($array)
	{
		return json_encode($array, ((empty($this->options['encode'])) ? (128 | 256) : (256)));
	}

	/**
	 * 获取JSON格式的数据表
	 * @access public
	 * @param string $option 默认为空 值为id时返回包括ID的数组数据
	 * @return array|false
	 */
	public function jsonFile()
	{
		if (!file_exists($this->tableFile)) {
			return [];
		}
		$data = file_get_contents($this->tableFile);
		$data = $this->options['decode'] ? $this->options['decode']($data) : $data;
		$data = json_decode($data, true);
		if (!is_array($data)) {
			$this->DbError('文件数据错误！');
		}
		if (empty($data)) {
			return [];
		}
		return $data;
	}

	/**
	 * 将数组数据存储到JSON数据表中
	 * @access private
	 * @param array $array 要存储的数组数据
	 * @param string $table_name 自定义表名
	 * @return int|false 成功则返回存储数据的总字节，失败则返回false
	 */
	private function arrayFile(array $array, $table_name = null)
	{
		$data = array_values($array);
		$data = $this->jsonEncode($data);
		if ($table_name) {
			$this->tableSwitch($table_name);
		}
		if (!file_exists($this->tableRoot)) {
			mkdir($this->tableRoot, 0755, true);
		}
		$data = $this->options['encode'] ? $this->options['encode']($data) : $data;
		return file_put_contents($this->tableFile, $data);
	}

	/**
	 * 输出一个错误信息
	 * @access private
	 * @param string $msg 错误信息
	 */
	private function DbError($msg)
	{
		$this->error = $msg;
		if ($this->options['debug']) {
			echo ('JsonDb Error：' . $msg);
			exit;
		}
	}
}

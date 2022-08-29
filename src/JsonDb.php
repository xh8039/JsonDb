<?php

/**
 * JsonDb
 * @Description JSON文件数据库
 * @version 1.1
 * @author 易航
 * @blog http://blog.bri6.cn
 * @gitee https://gitee.com/yh_IT/json-db
 */

namespace JsonDb\JsonDb;

class JsonDb
{
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

	/** JsonDb配置文件名称 */
	public $optionsTableName;

	//构造函数，初始化的时候最先执行
	public function __construct($options = [])
	{

		// 检测是否开启数据压缩模式
		if (@$options['compress_mode'] === false) {
			$options['compress_mode'] = '';
			$options['decompress_mode'] = '';
			$options['file_suffix'] = 'json';
		} else if (empty($options['compress_mode'])) {
			// 没有使用此参数配置那么默认使用'gzcompress'压缩
			$options['compress_mode'] = 'gzcompress';
		}

		$compress_function = [
			['gzcompress', 'gzuncompress', '.zlib'],
			['gzencode', 'gzdecode', '.gzip'],
			['gzdeflate', 'gzinflate', '.deflate'],
			['bzcompress', 'bzdecompress', 'bzip2']
		];

		// 寻找是否使用上方数组中的函数 如果有使用就自动配置解压函数和文件后缀名
		foreach ($compress_function as $value) {
			if ($options['compress_mode'] == $value[0]) {
				$options['decompress_mode'] = $value[1];
				$options['file_suffix'] = $value[2];
			}
		}

		// 自定义存储路径
		if (@$options['path']) {
			$options['path'] .= '/';
		} else {
			$options['path'] = '';
		}

		// 检测站点根目录
		if (@$_SERVER['DOCUMENT_ROOT']) {
			$this->dataRoot = $_SERVER['DOCUMENT_ROOT'] . '/';
		} else {
			$this->dataRoot = './';
		}

		// 数据存储的目录
		$this->tableRoot = $this->dataRoot . $options['path'] . 'json_data'; //存储的目录

		// 调试模式
		if (@$options['debug'] !== true) {
			$options['debug'] = false;
		}
		$this->optionsTableName = 'database_options';
		$this->options = $options;

		// 单表模式
		if (@$options['table_name']) {
			$this->table($options['table_name']);
		} else {
			$options['table_name'] = null;
		}
	}

	function initialize()
	{
		// 将表路径指向配置文件
		$this->tableSwitch($this->optionsTableName);

		// 检测表是否存在
		if (!$this->tableExists($this->optionsTableName)) {
			// 不存在便创建一个空表
			$this->arrayFile(array());
		}

		// 检测是否有表名 没有则无需下面操作
		if ((@!$this->tableName) && (!$this->options['table_name'])) {
			return false;
		}

		// 获取要添加配置文件的表的名字
		$table_name = $this->tableName ? $this->tableName : $this->options['table_name'];

		// 因为已经指向 tableFile 直接查询配置文件即可
		$table_options = $this->where('table_name', $table_name)->find();

		// 如果没有该表名的配置那么添加该表命的配置
		if (empty($table_options)) {
			$data = $this->jsonFile();
			if (empty($data)) {
				$data = [];
			}
			$data[] = [
				'table_name' => $table_name,
				'primary_key' => ['id'],
				'auto_increme_int' => [
					'id' => 0
				]
			];
			$this->arrayFile($data);
		}

		// 恢复以前指向的表路径
		$this->tableSwitch($table_name);
	}

	/**
	 * 添加单条数据
	 * @access public
	 * @param array $array 要添加的数据
	 * @return integer|false 成功则以int形式返回添加数据的总字节 失败则返回false
	 */
	public function insert(array $array)
	{
		// 调用主键检测
		if ($this->primaryKeyExists($array) === true) {
			return false;
		}

		// 获取表中原来的数据
		$data = $this->jsonFile();

		// 如果数据为空那么将表指定为一个空数组
		if (empty($data)) {
			$data = [];
		}

		// 获取被添加数据的表的配置项
		$table_options = $this->tableOptions();
		$auto_increme_int = $table_options['auto_increme_int'];
		if (is_array($auto_increme_int)) {
			foreach ($auto_increme_int as $key => $value) {
				$array[$key] = $value;
				$auto_increme_int[$key]++;
			}

			// 更新配置文件中的此表的自动递增值
			$this->tableSwitch($this->optionsTableName)->where('table_name', $this->tableName)->update([
				'auto_increme_int' => $auto_increme_int
			]);

			// 恢复原来表的路径
			$this->tableSwitch($this->tableName);
		}
		$num = $auto_increme_int['id'] - 1;
		$num = "$num";
		$data[$num] = $array;
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
			if ($insertAll == $this->limit) {
				break;
			}
			$insertAll++;
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
	 * 删除所有数据
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
			return null;
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
			return null;
		}
		return $where;
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
			return null;
		}
		if (count($data) == 1) {
			$data = $data[0];
		}
		return $data;
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
		$this->initialize();
		if (@!$this->limit) {
			$this->limit = null;
		}
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
	 * 添加数据表的主键
	 * @access public
	 * @param string|array $primary_key 要添加的主键 数组形式则批量添加
	 * @return $this
	 */
	public function primaryKeyAdd($primary_key)
	{
		return $this->tableOptionsAdd('primary_key', $primary_key);
	}

	/**
	 * 添加数据表的自动递增整数字段
	 * @access public
	 * @param string|array $increme_name
	 * @return $this
	 */
	public function autoIncremeIntAdd($increme_name)
	{
		return $this->tableOptionsAdd('auto_increme_int', $increme_name);
	}

	public function tableOptionsAdd($field_name, $field_value)
	{
		$table_name = $this->tableName;
		$table_options = $this->tableOptions();
		if ((is_array($table_options[$field_name])) && (!empty($table_options[$field_name]))) {
			$field_value_list = $table_options[$field_name];
		} else {
			$field_value_list = [];
		}

		if ($field_name == 'auto_increme_int') {
			if (is_array($field_value)) {
				foreach ($field_value as $value) {
					$field_value_list[$value] = 0;
				}
			} else {
				$field_value_list[$field_value] = 0;
			}
		} else {
			if (is_array($field_value)) {
				foreach ($field_value as $value) {
					$field_value_list[] = $value;
				}
			} else {
				$field_value_list[] = $field_value;
			}
			$field_value_list = array_values(array_unique($field_value_list));
		}


		$update = $this->tableSwitch($this->optionsTableName)->where('table_name', $table_name)->update([
			$field_name => $field_value_list
		]);
		if ($update) {
			return true;
		}
		return $update;
	}

	/**
	 * 获取表的配置
	 * @access public
	 * @param string $table_name 可选，自定义表名
	 * @return array
	 */
	public function tableOptions($table_name = null)
	{
		$table_name = $table_name ? $table_name : $this->tableName;
		$tableFile = $this->tableFile;
		$find = $this->tableSwitch($this->optionsTableName)->where('table_name', $table_name)->find();
		$this->tableFile = $tableFile;
		return $find;
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
	 * 检查添加数据的主键值是否存在
	 * @access public
	 * @param array $array 要检查的数据
	 * @param string $table_name 可选 数据表名
	 * @return bool
	 */
	private function primaryKeyExists($array, $table_name = null)
	{
		$table_name = $table_name ? $table_name : $this->tableName;
		$table_options = $this->tableSwitch($this->optionsTableName)->where('table_name', $table_name)->find();
		if (empty($table_options['primary_key'])) {
			return false;
		}
		if ((!is_array($table_options['primary_key']))) {
			return false;
		}
		$primary_key_list = $table_options['primary_key'];
		foreach ($primary_key_list as $primary_value) {
			foreach ($array as $key => $value) {
				if ($key == $primary_value) {
					$seek = $this->tableSwitch($table_name)->where($key, $value)->find();
					if ($seek) {
						$this->error = '当前插入数据中存在相同主键值';
						return true;
					}
				}
			}
		}
		$this->tableSwitch($this->tableName);
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
		$data = [];
		if (!is_array($field_name)) {
			$field = [];
			$field[] = [$field_name, $operator, $field_value];
		}
		foreach ($field as $field_key => $field_val) {
			$field_name = $field_val[0];
			$operator = (@isset($field_val[1]) ? $field_val[1] : null);
			$field_value = (@isset($field_val[2]) ? $field_val[2] : null);
			if (isset($field_name) && is_null($operator) && is_null($field_value)) {
				$operator = $field_name;
				$match = preg_match_all('/`field_([\w,\d]+)`/s', $operator, $match_array);
				if (!$match) {
					$this->error = '判断条件无效 请检查是否存在伪字段名：`field_字段名';
					return $this;
				}
				foreach ($match_array[1] as $key => $value) {
					$match_array[1][$key] = '$value[\'' . $value . '\']';
				}
				$str = str_replace($match_array[0], $match_array[1], $operator);
				$str = str_replace('`', '\'', $str);
				$str = 'return(' . $str . ');';
				foreach ($file as $key => $value) {
					$result = eval($str);
					if ($result) {
						$data[$key] = $file[$key];
					}
				}
				continue;
			}
			if (isset($field_name) && isset($operator) && is_null($field_value)) {
				$field_value  = $operator;
				foreach ($file as $key => $value) {
					if (@$value[$field_name] == $field_value) {
						$data[$key] = $file[$key];
					}
				}
				continue;
			}
			if (isset($field_name) && isset($operator) && isset($field_value)) {
				if (strtolower($operator) == 'like') {
					return $this->whereLike($field_name, $field_value);
				} else {
					$operator == '=' ? $operator = '==' : $operator = $operator;
					foreach ($file as $key => $value) {
						$str = 'return ' . $value[$field_name] . ' ' . $operator . ' ' . $field_value . ';';
						$result = eval($str);
						if ($result) {
							$data[$key] = $file[$key];
						}
					}
				}
				continue;
			}
		}
		$this->filterResult = $data;
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
		$data = [];
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
				$data[$key] = $file[$key];
			}
		}
		$this->filterResult = $data;
		return $this;
	}

	/**
	 * ORDER排序
	 * @access public
	 * @param string $field_name 字段名
	 * @param SORT_ASC|SORT_DESC $order 排序方式：SORT_ASC - 按升序排列|SORT_DESC - 按降序排列
	 * @return $this
	 */
	public function order($field_name, $order)
	{
		if (is_array($this->filterResult)) {
			$file = $this->filterResult;
		} else {
			$this->jsonFile();
		}
		array_multisort(array_column($file, $field_name), $order, $file);
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
		return json_encode($array, ((empty($this->options['compress_mode'])) ? (128 | 256) : (256)));
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
			return false;
		}
		$data = file_get_contents($this->tableFile);
		$data = $this->options['decompress_mode']($data);
		$data = json_decode($data, true);
		if (!is_array($data)) {
			if ($this->options['debug']) {
				$this->DbError('文件格式错误！');
				return;
			}
			return false;
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
		$data = $this->jsonEncode($array);
		if ($table_name) {
			$this->tableSwitch($table_name);
		}
		if (!file_exists($this->tableRoot)) {
			mkdir($this->tableRoot, 0755, true);
		}
		$data = $this->options['compress_mode']($data);
		return file_put_contents($this->tableFile, $data);
	}

	/**
	 * 输出一个错误信息
	 * @access private
	 * @param string $msg 错误信息
	 */
	private function DbError($msg)
	{
		echo ('JsonDb Error：' . $msg);
		if ($this->options['debug']) {
			exit;
		}
	}
}

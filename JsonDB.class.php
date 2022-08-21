<?php

/**
 * JsonDb
 * @Description JSON文件数据库
 * @version 1.1.3
 * @author 易航
 * @blog http://blog.bri6.cn
 * @gitee https://gitee.com/yh_IT/json-db
 */
class JsonDb
{

	//构造函数，初始化的时候最先执行
	public function __construct($options = [])
	{

		// 数据压缩模式
		if (@$options['data_type'] !== false) {
			$options['data_type'] = true;
		}

		// 自定义存储路径
		if (@$options['path']) {
			$options['path'] .= '/';
		} else {
			$options['path'] = '';
		}

		// 检测站点根目录
		if (@$_SERVER['DOCUMENT_ROOT']) {
			$this->DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] . '/';
		} else {
			$this->DOCUMENT_ROOT = './';
		}

		// 数据存储的目录
		$this->data_folder = $this->DOCUMENT_ROOT . $options['path'] . 'json_data'; //存储的目录

		// 单表模式
		if (@$options['table_name']) {
			$this->data_path = $this->data_folder . '/' . $options['table_name'] . ($options['data_type'] ? '' : '.json');
		}

		// 调试模式
		if (@$options['debug'] !== true) {
			$options['debug'] = false;
		}

		$this->options = $options;

		// 创建配置文件
		$this->optionsTableName = 'database_options';
		if (!$this->tableExists($this->optionsTableName)) {
			$this->table($this->optionsTableName)->array_file([]);
		}
	}

	/**
	 * 添加单条数据
	 * @access public
	 * @param array $array 要添加的数据
	 * @return int|false 成功则以int形式返回添加数据的总字节 失败则返回false
	 */
	public function insert(array $array)
	{
		if (file_exists($this->data_path)) {
			if ($this->primaryKeyMode) {
				$this->primaryKeyExists($array);
			}
			$data = $this->json_file();
		} else {
			$data = [];
		}
		$data[] = $array;
		return $this->array_file($data);
	}

	/**
	 * 批量添加数据
	 * @access public
	 * @param array $array 数据集
	 * @return integer
	 */
	public function insertAll(array $array)
	{
		if (file_exists($this->data_path)) {
			if ($this->primaryKeyMode) {
				foreach ($array as $value) {
					$this->primaryKeyExists($value);
				}
			}
			$data = $this->json_file();
		} else {
			$data = array();
		}
		$insertAll = 0;
		foreach ($array as $value) {
			$insertAll++;
			$data[] = $value;
			if ((!empty($this->limit)) && ($insertAll == $this->limit)) {
				break;
			}
		}
		if ($this->array_file($data)) {
			return $insertAll;
		} else {
			return false;
		}
	}

	/**
	 * 更新数据
	 * @access public
	 * @param array $array 要更新的数据
	 * @return integer
	 */
	public function update(array $array)
	{
		$file = $this->json_file();
		$update = 0;
		$where = $this->whereData;
		foreach ($where as $key => $value) {
			foreach ($array as $array_key => $array_value) {
				$update++;
				$file[$key][$array_key] = $array_value;
				if ((!empty($this->limit)) && ($update == $this->limit)) {
					break;
				}
			}
		}
		$this->array_file($file);
		$this->whereData = false;
		return $update;
	}

	/**
	 * 删除部分数据
	 * @access public
	 * @param array $array 要删除的部分数据字段名
	 * @return int
	 */
	public function delete(array $array)
	{
		$file = $this->json_file();
		$delete = 0;
		$where = $this->whereData;
		foreach ($where as $key => $value) {
			foreach ($array as $array_value) {
				$delete++;
				unset($file[$key][$array_value]);
				if ((!empty($this->limit)) && ($delete == $this->limit)) {
					break;
				}
			}
		}
		$this->array_file($file);
		$this->whereData = false;
		return $delete;
	}

	/**
	 * 删除所有数据
	 * @access public
	 * @param bool $type 删除整个表时使用布尔值true 否则留空
	 * @return int
	 */
	public function deleteAll($type = false)
	{
		if ($type === true) {
			return unlink($this->data_path);
		}
		$file = $this->json_file();
		$delete = 0;
		$where = $this->whereData;
		foreach ($where as $key => $value) {
			$delete++;
			unset($file[$key]);
			if ((!empty($this->limit)) && ($delete == $this->limit)) {
				break;
			}
		}
		$file = array_values($file);
		$this->array_file($file);
		$this->whereData = false;
		return $delete;
	}

	/**
	 * 查询单条数据
	 * @access public
	 * @return array
	 */
	public function find()
	{
		$where = $this->whereData;
		$this->whereData = false;
		foreach ($where as $key => $value) {
			if (@!$value['id']) {
				$where[$key]['id'] = $key;
			}
			return $value;
		}
		return null;
	}

	/**
	 * 查询多条数据
	 * @access public
	 * @return array
	 */
	public function select()
	{
		$where = $this->whereData;
		foreach ($where as $key => $value) {
			$select = true;
			if (@!$value['id']) {
				$where[$key]['id'] = $key;
			}
		}
		$this->whereData = false;
		if (!$select) {
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
		$data = $this->json_file('id');
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
		$file = @$this->whereData ? $this->whereData : $this->json_file();
		$data = [];
		if (is_null($length)) {
			$length = count($file);
		}
		foreach ($file as $key => $value) {
			if ($key > $offset || $key < $length) {
				$data[$key] = $value;
			}
		}
		$this->whereData = $data;
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
		$this->data_path = "$this->data_folder/$this->optionsTableName" . ($this->options['data_type'] ? '' : '.json');
		$table_options = $this->where('table_name', $table_name)->find();
		if (!$table_options) {
			$this->insert([
				'table_name' => $table_name
			]);
		}
		if ((is_array($table_options['primary_key'])) && (!empty($table_options['primary_key']))) {
			$this->primaryKeyMode = true;
		} else {
			$this->primaryKeyMode = false;
		}

		$this->data_path = "$this->data_folder/$table_name" . ($this->options['data_type'] ? '' : '.json');
		$this->tableName = $table_name;
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
		$table_name = $this->tableName;
		$table_options = $this->table($this->optionsTableName)->where('table_name', $table_name)->find();
		if ((is_array($table_options['primary_key'])) && (!empty($table_options['primary_key']))) {
			$primary_key_list = $table_options['primary_key'];
		} else {
			$primary_key_list = [];
		}
		if (is_array($primary_key)) {
			foreach ($primary_key as $value) {
				$primary_key_list[] = $value;
			}
		} else {
			$primary_key_list[] = $primary_key;
		}
		$primary_key_list = array_values(array_unique($primary_key_list));
		$update = $this->table($this->optionsTableName)->where('table_name', $table_name)->update([
			'primary_key' => $primary_key_list
		]);
		if ($update) {
			return true;
		}
		return $update;
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
			$data_path = "$this->data_folder/$table_name" . ($this->options['data_type'] ? '' : '.json');
		} else {
			$data_path = $this->data_path;
		}
		if (file_exists($data_path)) {
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
		$table_options = $this->table($this->optionsTableName)->where('table_name', $table_name)->find();
		if ((!is_array($table_options['primary_key'])) || (empty($table_options['primary_key']))) {
			return false;
		}
		$primary_key_list = $table_options['primary_key'];
		foreach ($primary_key_list as $primary_value) {
			foreach ($array as $key => $value) {
				if ($key == $primary_value) {
					$seek = $this->table($table_name)->where($key, $value)->find();
					if ($seek) {
						$this->DbError('当前插入数据中存在相同主键值');
						return true;
					}
					return false;
				}
			}
		}
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
		$file = @$this->whereData ? $this->whereData : $this->json_file();
		if (!is_array($file)) {
			$this->whereData = [];
			return $this;
		}
		$data = [];
		if (func_num_args() == 1) {
			$operator = $field_name;
			$match = preg_match_all('/`field_([\w,\d]+)`/s', $operator, $match_array);
			if (!$match) {
				$this->DbError('判断条件无效 请检查是否存在伪字段名：`field_字段名');
				return;
			}
			foreach ($match_array[1] as $key => $value) {
				$match_array[1][$key] = '$value[\'' . $value . '\']';
			}
			$str = str_replace($match_array[0], $match_array[1], $operator);
			$str = str_replace('`', '\'', $str);
			$str = 'return(' . $str . ');';
			foreach ($file as $key => $value) {
				if (@!$value['id']) {
					$value['id'] = $key;
				}
				$result = @eval($str);
				if ($result) {
					$data[$key] = $file[$key];
				}
			}
		}
		if (func_num_args() == 2) {
			if (is_null($field_value)) {
				$field_value  = $operator;
			}
			foreach ($file as $key => $value) {
				if (@!$value['id']) {
					$value['id'] = $key;
				}
				if (@$value[$field_name] == $field_value) {
					$data[$key] = $file[$key];
				}
			}
		}
		if (func_num_args() == 3) {
			if (strtolower($operator) == 'like') {
				$this->whereLike($field_name, $field_value);
			}
			$operator == '=' ? $operator = '==' : $operator = $operator;
			foreach ($file as $key => $value) {
				if (@!$value['id']) {
					$value['id'] = $key;
				}
				$str = 'return ' . $value[$field_name] . ' ' . $operator . ' ' . $field_value . ';';
				$result = @eval($str);
				if ($result) {
					$data[$key] = $file[$key];
				}
			}
		}
		$this->whereData = $data;
		return $this;
	}

	/**
	 * LIKE查询
	 * @access public
	 * @param string $field 字段名
	 * @param mixed $value 数据
	 * @return $this
	 */
	public function whereLike($field_name, $field_value)
	{
		$file = @$this->whereData ? $this->whereData : $this->json_file();
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
			if (@!$value['id']) {
				$value['id'] = $key;
			}
			if (preg_match($field_value, @$value[$field_name]) > 0) {
				$data[$key] = $file[$key];
			}
		}
		$this->whereData = $data;
		return $this;
	}

	/**
	 * 数组转JSON数据
	 * @access public
	 * @param array $array 要转换的数组
	 * @return json|string
	 */
	public function json_encode($array)
	{
		return json_encode($array, ($this->options['data_type'] ? 256  : 128 | 256));
	}

	/**
	 * 获取JSON格式的数据表
	 * @access public
	 * @param string $option 默认为空 值为id时返回包括ID的数组数据
	 * @return array|false
	 */
	public function json_file($option = false)
	{
		if (!file_exists($this->data_path)) {
			if ($this->options['debug']) {
				$this->DbError('找不到数据文件 查找文件路径为：' . $this->data_path);
				return;
			}
			return false;
		}
		$data = file_get_contents($this->data_path);
		$data = json_decode(($this->options['data_type'] ? gzuncompress($data) : $data), true);
		if (!is_array($data)) {
			if ($this->options['debug']) {
				$this->DbError('文件格式错误！');
				return;
			}
			return false;
		}
		if ($option == 'id') {
			foreach ($data as $key => $value) {
				if (@!$value['id']) {
					$data[$key]['id'] = $key;
				}
			}
		}
		return $data;
	}

	/**
	 * 将数组数据存储到JSON数据表中
	 * @access public
	 * @param array $array 要存储的数组数据
	 * @param string $table_name 自定义表名
	 * @return int|false 成功则返回存储数据的总字节，失败则返回false
	 */
	private function array_file($array, $table_name = null)
	{
		if (!is_array($array)) {
			$this->DbError('传入参数非数组！');
			return;
		}
		$data = $this->json_encode($array);
		if ($table_name) {
			$this->table($table_name);
		}
		if (!file_exists($this->data_folder)) {
			mkdir($this->data_folder, 0755, true);
		}
		return file_put_contents($this->data_path, ($this->options['data_type'] ? gzcompress($data) : $data));
	}

	/**
	 * 输出一个错误信息
	 * @access public
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

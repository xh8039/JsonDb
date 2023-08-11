<?php

namespace JsonDb\JsonDb;

/**
 * @package JsonDb
 * @author  易航
 * @version 2.30
 * @link    https://gitee.com/yh_IT/json-db
 *
 */
class JsonDb
{

	/** 自定义配置项 */
	public $options = [
		'table_name' => null, // 单表模式
		'encode' => null, // 加密函数
		'decode' => null, // 解密函数
		'file_suffix' => '.json', // 文件后缀名
		'path' => null, // 自定义存储路径
		'debug' => true, // 调试模式
	];

	public $table_options = [
		'admin' => [
			'auto_increme_fields' => ['id'], // 设置表中自动递增整数字段
			'primary_key_fields' => ['id'] // 设置表中的主键字段
		],
		'options' => [
			'auto_increme_fields' => [], // 设置表中自动递增整数字段
			'primary_key_fields' => ['name'] // 设置表中的主键字段
		],
	];

	/** 错误信息 */
	public $error;

	/** JSON数据存储文件夹的根目录 */
	public $tableRoot;

	/** JSON数据表的文件路径 */
	public $tableFile;

	/** 筛选后的结果 */
	public $filterResult = null;

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

		if (empty($this->options['path'])) $this->DbError('请配置数据表的存储目录');

		// 数据存储的目录
		$this->tableRoot = $this->options['path'] . DIRECTORY_SEPARATOR;

		$this->tableRoot = str_replace(['//', '\\\\'], ['/', '\\'], $this->tableRoot);

		// 单表模式
		$this->options['table_name'] ? $this->table($this->options['table_name']) : false;
	}

	/**
	 * 字段值增长
	 * @access public
	 * @param string  $field    字段名
	 * @param float   $step     增长值
	 * @return $this
	 */
	public function inc(string $field, float $step = 1)
	{
		$where = $this->filterResult;
		if (empty($where)) {
			return $this;
		}
		foreach ($where as $key => $value) {
			if (is_numeric($value[$field])) {
				$this->filterResult[$key][$field] = $value[$field] + $step;
			}
		}
		return $this;
	}

	/**
	 * 字段值减少
	 * @access public
	 * @param string  $field    字段名
	 * @param float   $step     增长值
	 * @return $this
	 */
	public function dec(string $field, float $step = 1)
	{
		$where = $this->filterResult;
		if (empty($where)) {
			return $this;
		}
		foreach ($where as $key => $value) {
			if (is_numeric($value[$field])) {
				$this->filterResult[$key][$field] = $value[$field] - $step;
			}
		}
		return $this;
	}

	/**
	 * 插入记录
	 * @access public
	 * @param array   $data         数据
	 * @param boolean $getLastInsID 返回自增主键
	 * @return integer|string
	 */
	public function insert(array $data = [], bool $getLastInsID = false)
	{
		// 获取表中原来的数据
		$file = $this->jsonFile();
		$end_data = end($file);
		$data['id'] = is_numeric(@$end_data['id']) ? $end_data['id'] + 1 : 1;
		$data['create_time'] = isset($data['create_time']) ? $data['create_time'] : date('Y-m-d H:i:s');
		array_push($file, $data);
		if ($getLastInsID) {
			$this->arrayFile($file);
			return $data['id'];
		}
		return $this->arrayFile($file);
	}

	/**
	 * 插入记录并获取自增ID
	 * @access public
	 * @param array $data 数据
	 * @return integer|string
	 */
	public function insertGetId(array $data)
	{
		return $this->insert($data, true);
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

	public function save(array $array, $primary_key = 'id')
	{
		// 检查要保存的数据中是否存在主键数据
		if (isset($array[$primary_key])) {
			$value = $array[$primary_key];
			// 查询数据表中数据是否存在
			$find = $this->where($primary_key, $value)->find();
			if ($find) {
				unset($array[$primary_key]);
				return $this->where($primary_key, $value)->update($array);
			}
		}
		return $this->insert($array);
	}

	/**
	 * 更新记录
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
				if (!isset($array['update_time'])) {
					$file[$key]['update_time'] = date('Y-m-d H:i:s');
				}
				if ($update == $this->limit) {
					break;
				}
			}
		}
		$this->arrayFile($file);
		$this->filterResult = null;
		return $update;
	}

	/**
	 * 删除数据
	 * @access public
	 * @param array|bool $data 要删除的数据数组字段名，不传值则删除整列数据，删除整个表数据传布尔值 true
	 * @return integer  返回影响数据的键值数量
	 */
	public function delete($data)
	{
		if (is_array($data)) {
			return $this->deleteField($data);
		} else {
			return $this->deleteAll($data);
		}
	}

	/**
	 * 删除部分数据
	 * @access public
	 * @param array $array 要删除的部分数据字段名
	 * @return integer  返回影响数据的键值数量
	 */
	public function deleteField(array $array)
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
		$this->filterResult = null;
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
		$this->filterResult = null;
		return $delete;
	}

	/**
	 * 查询单条数据
	 * @param $id 通过ID查询指定数据
	 * @access public
	 * @return array
	 */
	public function find($id = null)
	{
		if (is_numeric($id)) {
			$this->where('id', $id);
		}
		$where = $this->filterResult;
		$this->filterResult = null;
		if (empty($where)) {
			return null;
		}
		return current($where);
	}

	public function value($field_name)
	{
		$find = $this->find();
		if (isset($find[$field_name])) {
			return $find[$field_name];
		}
		return $find;
	}

	/**
	 * 查询多条数据
	 * @access public
	 * @param bool $key
	 * @return array
	 */
	public function select(bool $key = false)
	{
		if (is_null($this->filterResult)) {
			return $this->selectAll($key);
		}
		$where = $this->filterResult;
		$this->filterResult = null;
		if (empty($where)) {
			return [];
		}
		if ($key) return $where;
		return array_values($where);
	}

	/**
	 * 查询所有数据
	 * @access public
	 * @return array
	 */
	public function selectAll($key = false)
	{
		$data = $this->jsonFile();
		if (empty($data)) {
			return [];
		}
		if ($key) return $data;
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
		$this->filterResult = null;
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
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
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
		if (empty($table_name)) {
			$this->DbError('表名不能为空');
			return;
		}
		$this->tableFile = $this->tableRoot . DIRECTORY_SEPARATOR . $table_name . $this->options['file_suffix'];
		// $this->tableName = $table_name;
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
		$this->tableFile = $this->tableRoot . DIRECTORY_SEPARATOR . $table_name . $this->options['file_suffix'];
		return $this;
	}

	/**
	 * 根据字段条件过滤数组中的元素
	 * @access public
	 * @param string $field_name 字段名
	 * @param mixed  $operator 操作符 默认为 ==
	 * @param mixed  $field_value 字段值
	 * @return $this
	 */
	public function where($a, $b = null, $c = null)
	{
		$param = func_num_args();
		if ($param == 1 && is_array($a)) {
			$this->whereArray($a);
		}
		if ($param == 2) {
			$this->whereOperator($a, '=', $b);
		}
		if ($param == 3) {
			$this->whereOperator($a, $b, $c);
		}
		return $this;
	}

	/**
	 * 根据字段条件过滤数组中的元素
	 * @access private
	 * @param string $field_name 字段名
	 * @param mixed  $operator 操作符 默认为 ==
	 * @param mixed  $field_value 字段值
	 * @return $this
	 */
	private function whereOperator($field_name, $operator, $field_value)
	{
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
		if (!is_array($file)) {
			$this->filterResult = [];
			return $this;
		}
		switch ($operator) {
			case '=':
				foreach ($file as $key => $value) {
					if ($value[$field_name] != $field_value) unset($file[$key]);
				}
				break;
			case '>':
				foreach ($file as $key => $value) {
					if (!($value[$field_name] > $field_value)) unset($file[$key]);
				}
				break;
			case '>=':
				foreach ($file as $key => $value) {
					if (!($value[$field_name] >= $field_value)) unset($file[$key]);
				}
				break;
			case '<':
				foreach ($file as $key => $value) {
					if (!($value[$field_name] < $field_value)) unset($file[$key]);
				}
				break;
			case '<=':
				foreach ($file as $key => $value) {
					if (!($value[$field_name] <= $field_value)) unset($file[$key]);
				}
				break;
			default:
		}
		$this->filterResult = $file;
		return $this;
	}

	private function whereArray(array $array)
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if (isset($value[2])) {
					$this->where($value[0], $value[1], $value[2]);
				} else {
					$this->where($value[0], $value[1]);
				}
			} else {
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
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
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
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
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
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
		$keys = array_keys($file);
		$offset = array_search($field_name, $keys);
		$this->filterResult = array_slice($file, $offset + 1);
		return $this;
	}

	/**
	 * ORDER排序
	 * @access public
	 * @param string $field_name 字段名
	 * @param $order 排序方式：['asc' => 按升序排列,'desc' => 按降序排列]
	 * @return $this
	 */
	public function order($field_name, $order = 'desc')
	{
		$order_list = [
			'asc' => SORT_ASC,
			'desc' => SORT_DESC
		];
		$order_this = $order_list[$order] ? $order_list[$order] : $order;
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
		foreach ($file as $key => $value) {
			if (!isset($value[$field_name])) {
				$file[$key][$field_name] = ($order == 'desc' ? 0 : 99999999);
			}
		}
		$column = array_column($file, $field_name);
		array_multisort($column, $order_this, $file);
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
		return json_encode($array, ((empty($this->options['encode'])) ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (JSON_UNESCAPED_UNICODE)));
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
		$data = empty($this->options['decode']) ? $data : call_user_func($this->options['decode'], $data);
		$data = json_decode($data, true);
		if (!is_array($data)) {
			$this->DbError('文件' . $this->tableFile . '数据错误！');
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
		$data = empty($this->options['encode']) ? $data : call_user_func($this->options['encode'], $data);
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

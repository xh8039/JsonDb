<?php

namespace JsonDb\JsonDb;

/**
 * @package JsonDb
 * @author  易航
 * @version dev
 * @link    https://gitee.com/yh_IT/json-db
 * 易航天地
 * http://bri6.cn
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

	/** 错误信息 */
	public $error;

	/** JSON数据存储文件夹的根目录 */
	public $tableRoot;

	/** JSON数据表的文件路径 */
	public $tableFile;

	/** JSON数据表的名称 */
	public $tableName;

	/** 筛选后的结果 */
	public $filterResult = null;

	/** 对数据限制的处理条数 */
	public $limit = null;

	/** 要合并原来数据的字段 */
	private array $merge = [];

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

		$this->tableRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->tableRoot);

		// 单表模式
		$this->options['table_name'] ? $this->table($this->options['table_name']) : false;
	}

	/**
	 * 将数据打乱
	 * @return JsonDb
	 */
	public function shuffle()
	{
		if (is_null($this->filterResult)) {
			$this->filterResult = $this->jsonFile();
		}
		shuffle($this->filterResult);
		return $this;
	}

	/**
	 * 要进行数组递归替换的字段
	 * @param array|string $field
	 * @return JsonDb
	 */
	public function merge(array|string $field)
	{
		if (is_string($field)) {
			$this->merge[] = $field;
		} else {
			$this->merge = array_merge($this->merge, $field);
		}
		return $this;
	}

	/**
	 * 字段值增长
	 * @access public
	 * @param string  $field    字段名
	 * @param float   $step     增长值
	 * @return JsonDb
	 */
	public function inc(string $field, float $step = 1)
	{
		$result = $this->filterResult;
		if (empty($result)) {
			return $this;
		}
		foreach ($result as $key => $value) {
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
	 * @return JsonDb
	 */
	public function dec(string $field, float $step = 1)
	{
		$result = $this->filterResult;
		if (empty($result)) {
			return $this;
		}
		foreach ($result as $key => $value) {
			if (is_numeric($value[$field])) {
				$this->filterResult[$key][$field] = $value[$field] - $step;
			}
		}
		return $this;
	}

	/**
	 * 新增记录
	 * @access public
	 * @param array   $data         数据
	 * @param boolean $getLastInsID 返回自增主键
	 * @return integer|string
	 */
	public function insert(array $data = [], bool $getLastInsID = false)
	{
		// 获取表中原来的数据
		$file = $this->jsonFile();
		// 获取原来数据数组中最后一行
		$end_data = end($file);
		$data['id'] = is_numeric(@$end_data['id']) ? $end_data['id'] + 1 : 1;
		$data['create_time'] = isset($data['create_time']) ? $data['create_time'] : date('Y-m-d H:i:s');
		$data['update_time'] = isset($data['update_time']) ? $data['update_time'] : $data['create_time'];
		array_push($file, $data);
		$storage = $this->arrayFile($file);
		return $getLastInsID ? $data['id'] : $storage;
	}

	/**
	 * 新增记录并获取自增ID
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
		// 获取表中原来的数据
		$file = $this->jsonFile();
		// 获取原来数据数组中最后一行
		$end_data = end($file);
		$start = is_numeric(@$end_data['id']) ? $end_data['id'] + 1 : 1;
		$insertAll = 0;
		foreach ($array as $key => $data) {
			$insertAll++;
			if ($insertAll === $this->limit) break;
			$data['id'] = $start;
			$data['create_time'] = isset($data['create_time']) ? $data['create_time'] : date('Y-m-d H:i:s');
			$data['update_time'] = isset($data['update_time']) ? $data['update_time'] : $data['create_time'];
			array_push($file, $data);
			$start++;
		}
		$this->arrayFile($file);
		return $insertAll;
	}

	/**
	 * 批量添加数据
	 * @access public
	 * @param array $array 数据集
	 * @return integer 返回共添加数据的条数
	 */
	// public function insertAll(array $array)
	// {
	// 	$insertAll = 0;
	// 	foreach ($array as $value) {
	// 		$insertAll++;
	// 		if ($insertAll === $this->limit) break;
	// 		$this->insert($value);
	// 	}
	// 	return $insertAll;
	// }

	/**
	 * 保存数据
	 * @access public
	 * @param array $array 要保存的数组数据
	 * @param $primary_key 主键名，要保存的数据中需要有这个键名
	 */
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
			} else {
				return $this->insert($array);
			}
		}
		return false;
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
		$result = $this->filterResult;
		foreach ($result as $key => $value) {
			foreach ($array as $array_key => $array_value) {
				$update++;
				if (!empty($this->merge) && is_array($this->merge) && in_array($array_key, $this->merge)) {
					$file[$key][$array_key] = array_replace_recursive($file[$key][$array_key], $array_value);
				} else {
					$file[$key][$array_key] = $array_value;
				}
				if (!isset($array['update_time'])) {
					$file[$key]['update_time'] = date('Y-m-d H:i:s');
				}
				if ($update == $this->limit) break;
			}
		}
		$this->arrayFile($file);
		// $this->filterResult = null;
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
		$result = $this->filterResult;
		foreach ($result as $key => $value) {
			foreach ($array as $array_value) {
				$delete++;
				unset($file[$key][$array_value]);
				if ($delete == $this->limit) {
					break;
				}
			}
		}
		$this->arrayFile($file);
		// $this->filterResult = null;
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
		$result = $this->filterResult;
		foreach ($result as $key => $value) {
			$delete++;
			unset($file[$key]);
			if ($delete == $this->limit) {
				break;
			}
		}
		$this->arrayFile($file);
		// $this->filterResult = null;
		return $delete;
	}

	/**
	 * 查询单条数据
	 * @param $id 通过ID查询指定数据
	 * @access public
	 * @return array|null
	 */
	public function find($id = null)
	{
		if (is_numeric($id)) {
			$this->where('id', $id);
		}
		$result = $this->filterResult;
		// $this->filterResult = null;
		if (empty($result)) return null;
		return current($result);
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
		$result = $this->filterResult;
		// $this->filterResult = null;
		if (empty($result)) return [];
		if ($key) return $result;
		return array_values($result);
	}

	/**
	 * 查询所有数据
	 * @access public
	 * @return array
	 */
	public function selectAll($key = false)
	{
		$data = $this->jsonFile();
		if (empty($data)) return [];
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
		$result = $this->filterResult;
		$data = is_null($result) ? $this->jsonFile() : $result;
		if (empty($data)) return 0;
		return count($data);
	}

	/**
	 * 指定查询数量
	 * @access public
	 * @param int $offset 起始位置
	 * @param int $length 查询数量
	 * @return JsonDb
	 */
	public function limit(int $offset, int $length = null)
	{
		if ($offset == 0 && $length == null) {
			return $this;
		}
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
	 * @return JsonDb
	 */
	public function table($table_name)
	{
		if (empty($table_name)) {
			$this->DbError('表名不能为空');
			return null;
		}
		$this->tableFile = $this->tableRoot . $table_name . $this->options['file_suffix'];
		$this->tableName = $table_name;
		return $this;
	}

	/**
	 * 内部调用表切换
	 * @access public
	 * @param string $table_name 要切换的数据表名
	 * @return JsonDb
	 */
	private function tableSwitch($table_name)
	{
		$this->tableFile = $this->tableRoot . $table_name . $this->options['file_suffix'];
		return $this;
	}

	/**
	 * 根据字段条件进行时间筛选
	 * @access public
	 * @param string $field_name 时间名
	 * @param string $operator 操作符
	 * @param string|array $field_value 时间值
	 * @return JsonDb
	 */
	public function whereTime(string $field_name, string $operator, $field_value)
	{
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
		if (!is_array($file)) {
			$this->filterResult = [];
			return $this;
		}

		$filtered = [];

		$filtered = array_filter($file, function ($item) use ($field_name, $operator, $field_value) {
			// 检测字段值是否存在
			if (!isset($item[$field_name])) return false;
			// 如果要筛选的值是数组
			if (is_array($field_value)) {
				// 将数组内所有时间值解析为 Unix 时间戳
				$field_value_time = [];
				foreach ($field_value as $value) {
					$field_value_time[] = is_numeric($value) ? $value : strtotime($value);
				}
			} else {
				// 将时间值解析为 Unix 时间戳
				$field_value_time = is_numeric($field_value) ? $field_value : strtotime($field_value);
			}
			return $this->compare($field_name, $operator, $field_value_time, $item[$field_name]);
		});

		$this->filterResult = $filtered;
		return $this;
	}

	/**
	 * 根据字段条件过滤数组中的元素
	 * @access public
	 * @param string|array $a 字段名|筛选条件数组
	 * @param mixed  $b 操作符|字段值
	 * @param mixed  $c 字段值
	 * @return JsonDb
	 */
	public function where($a, $b = null, $c = null)
	{
		$param = func_num_args();
		if ($param == 1 && is_array($a)) $this->whereArray($a);
		if ($param == 2) $this->filter($a, '=', $b);
		if ($param == 3) {
			if ($b == 'like') return $this->whereLike($a, $c);
			$this->filter($a, $b, $c);
		}
		return $this;
	}

	/**
	 * 根据字段过滤数组
	 * @param string $field_name 字段名
	 * @param string $operator 操作符
	 * @param mixed $field_value 字段值
	 * @return JsonDb
	 */
	private function filter(string $field_name, string $operator, $field_value)
	{
		$file = is_null($this->filterResult) ? $this->jsonFile() : $this->filterResult;
		if (!is_array($file)) {
			$this->filterResult = [];
			return $this;
		}

		$filtered = [];

		$filtered = array_filter($file, function ($item) use ($field_name, $operator, $field_value) {
			if (!isset($item[$field_name])) return false;
			return $this->compare($item[$field_name], $operator, $field_value, $item);
		});

		$this->filterResult = $filtered;
		return $this;
	}

	/**
	 * 比较值
	 * @return bool
	 */
	private function compare($filter_name, $operator, $filter_value, $item = null)
	{
		switch ($operator) {
			case '=':
				return $filter_name == $filter_value;
			case '>':
				return $filter_name > $filter_value;
			case '>=':
				return $filter_name >= $filter_value;
			case '<':
				return $filter_name < $filter_value;
			case '<=':
				return $filter_name <= $filter_value;
			case '==':
				return $filter_name === $filter_value;
			case 'between':
				if (($item[$filter_name] >= reset($filter_value)) && ($item[$filter_name] <= end($filter_value))) {
					return true;
				}
				return false;
			case 'not between':
				if (($item[$filter_name] >= reset($filter_value)) && ($item[$filter_name] <= end($filter_value))) {
					return false;
				}
				return true;
			default:
				return false;
		}
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
	 * @return JsonDb
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
		if (is_string($field_name) && strstr($field_name, '|')) {
			$field_name = explode('|', $field_name);
		}
		foreach ($file as $file_key => $file_value) {
			$unset = true;
			if (is_array($field_name)) {
				foreach ($field_name as $explode_value) {
					if (preg_match($field_value, @$file_value[$explode_value]) > 0) {
						$unset = false;
						break;
					}
				}
			} else {
				if (preg_match($field_value, @$file_value[$field_name]) > 0) {
					$unset = false;
				}
			}
			if ($unset === true) unset($file[$file_key]);
		}
		$this->filterResult = $file;
		return $this;
	}

	/**
	 * 查询指定键名之前的数据
	 * @access public
	 * @param string $field_name 字段名
	 * @return JsonDb
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
	 * @return JsonDb
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
	 * @param $order asc 按升序排列丨desc 按降序排列
	 * @return JsonDb
	 */
	public function order($field_name, $order = 'desc')
	{
		$order_list = ['asc' => SORT_ASC, 'desc' => SORT_DESC];
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
	 * @access private
	 * @param array $array 要转换的数组
	 * @return string
	 */
	private function jsonEncode($array)
	{
		/**
		 * JSON_PRETTY_PRINT 用空白字符格式化返回的数据
		 * JSON_UNESCAPED_UNICODE 以字面编码多字节 Unicode 字符（默认是编码成 \uXXXX）
		 * JSON_UNESCAPED_SLASHES 不编码 /
		 */
		if ($this->options['debug']) {
			return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		} else {
			return json_encode($array);
		}
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
			$this->DbError('数据表 ' . $this->tableName . ' 不存在！');
			return [];
		}
		$data = file_get_contents($this->tableFile);
		$data = empty($this->options['decode']) ? $data : $this->options['decode']($data);
		$data = json_decode($data, true);
		if (!is_array($data)) {
			$this->DbError('数据表 ' . $this->tableName . ' 的JSON数据错误！');
		}
		if (empty($data)) return [];
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
		if ($table_name) $this->tableSwitch($table_name);
		if (!file_exists($this->tableRoot)) mkdir($this->tableRoot, 0755, true);
		$data = empty($this->options['encode']) ? $data : $this->options['encode']($data);
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
		if ($this->isAjax()) {
			echo $this->jsonEncode([
				'code' => 500,
				'msg' => $msg,
				'message' => $msg
			]);
		} else {
			echo ('JsonDb Error：' . $msg);
		}
		exit;
	}

	private function isAjax()
	{
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			// 是AJAX请求
			return true;
		} else {
			// 不是AJAX请求
			return false;
		}
	}
}

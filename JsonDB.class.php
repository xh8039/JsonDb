<?php

/**
 * JsonDB
 * @Description 纯JSON文件数据库
 * @version 1.1.1
 * @author 易航
 * @link http://blog.bri6.cn
 */
class JsonDB
{

	//构造函数，初始化的时候最先执行
	public function __construct($options = [])
	{
		@$options['path'] = $options['path'] ? $options['path'] : '';
		if (@$options['data_type'] !== false) {
			$options['data_type'] = true;
		}
		if ($options['path']) {
			$options['path'] .= '/';
		}
		if ($_SERVER['DOCUMENT_ROOT']) {
			$this->DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] . '/';
		} else {
			$this->DOCUMENT_ROOT = '.';
		}
		if (@$options['data_path']) {
			$this->data_folder = $this->DOCUMENT_ROOT . $options['path'] . 'json_data'; //存储的目录
			$this->data_path = $this->data_folder . '/' . $options['data_path'] . ($options['data_type'] ? '' : '.json');
		}
		$this->options = $options;
	}

	/**
	 * 添加单条数据
	 * @param $array 要添加的数组
	 */
	public function insert(array $array)
	{
		if (file_exists($this->data_path)) {
			$data = $this->json_file();
		} else {
			@mkdir($this->data_folder, 0755, true);
			$data = [];
		}
		$data[] = $array;
		return $this->array_file($data);
	}

	/**
	 * 添加多条数据
	 * @param $array 要添加的数组
	 */
	public function insertAll(array $array)
	{
		if (file_exists($this->data_path)) {
			$data = $this->json_file();
		} else {
			@mkdir($this->data_folder, 0755, true);
			$data = array();
		}
		$insertAll = 0;
		foreach ($array as $value) {
			$insertAll++;
			$data[] = $value;
		}
		if ($this->array_file($data)) {
			return $insertAll;
		} else {
			return false;
		}
	}

	/**
	 * 更新数据
	 * @param $array 要更新的数组 保留原本数据
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
			}
		}
		$this->array_file($file);
		$this->whereData = false;
		return $update;
	}

	/**
	 * 删除部分数据
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
			}
		}
		$this->array_file($file);
		$this->whereData = false;
		return $delete;
	}

	/**
	 * 删除所有数据
	 * @param $type 删除整个表时使用布尔值true 否则留空
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
		}
		$data = [];
		foreach ($file as $value) {
			$data[] = $value;
		}
		$this->array_file($data);
		$this->whereData = false;
		return $delete;
	}

	/**
	 * 查询单条数据
	 */
	public function find()
	{
		$where = $this->whereData;
		$this->whereData = false;
		foreach ($where as $key => $value) {
			if (@!$value['id']) {
				$value['id'] = $key;
			}
			return $value;
		}
		return null;
	}

	/**
	 * 查询多条数据
	 */
	public function select()
	{
		$where = $this->whereData;
		foreach ($where as $key => $value) {
			$select = true;
			if (!$value['id']) {
				$value['id'] = $key;
			}
		}
		$this->whereData = false;
		if (!$select) {
			return null;
		}
		return $where;
	}

	// 查询所有数据
	public function selectAll()
	{
		$data = $this->json_file('id');
		if (count($data) == 1) {
			$data = $data[0];
		}
		return $data;
	}

	public function table($data_path)
	{
		$this->data_folder = $this->DOCUMENT_ROOT . $this->options['path'] . 'json_data'; //存储的目录
		$this->data_path = "$this->data_folder/$data_path" . ($this->options['data_type'] ? '' : '.json');
		return $this;
	}
	public function where($field_name, $field_value)
	{
		$file = @$this->whereData ? $this->whereData : $this->json_file();
		$data = [];
		foreach ($file as $key => $value) {
			if (@!$value['id']) {
				$value['id'] = $key;
			}
			if (@$value[$field_name] == $field_value) {
				$data[$key] = $file[$key];
			}
		}
		$this->whereData = $data;
		return $this;
	}
	public function json_encode($array)
	{
		return json_encode($array, ($this->options['data_type'] ? 256  : 128 | 256));
	}
	public function json_file($option = false)
	{
		if (!file_exists($this->data_path)) {
			$this->DbError('找不到数据文件 查找文件路径为：' . $this->data_path);
		}
		$data = file_get_contents($this->data_path);
		$data = json_decode(($this->options['data_type'] ? gzuncompress($data) : $data), true);
		if (!is_array($data)) {
			$this->DbError('文件格式错误！');
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
	private function array_file($array)
	{
		if (!is_array($array)) {
			$this->DbError('传入参数非数组！');
		}
		$data = $this->json_encode($array);
		return file_put_contents($this->data_path, ($this->options['data_type'] ? gzcompress($data) : $data));
	}
	private function DbError($msg)
	{
		exit('JsonDB Error：' . $msg);
	}
}

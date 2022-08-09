<?php

/**
 * JsonDB
 * @Description 纯json文件数据库
 * @version 1.0.0
 * @author 易航
 * @link http://blog.bri6.cn
 */
class JsonDB
{
	//定义数据库文件名称
	private $data_path;

	//构造函数，初始化的时候最先执行
	public function __construct($data_path)
	{
		$this->data_folder = 'JsonData';
		$this->data_path = "./$this->data_folder/$data_path.json";
	}
	/**
	 * 添加数据 初始化时建议采用
	 * @param $array 要添加的数组
	 */
	public function insert(array $array)
	{
		if (file_exists($this->data_path)) {
			$data = $this->json_file();
			$data[] = $array;
			return $this->array_file($data);
		} else {
			mkdir($this->data_folder, 0755, true);
			$data = [];
			$data[] = $array;
			return $this->array_file($data);
		}
	}

	/**
	 * 更新数据
	 * @param $array 要更新的数组 保留原本数据
	 * @param $k 指定的数据键
	 * @param $val 指定的数据键值
	 */
	public function update(array $array, $k, $val)
	{
		$file = $this->json_file();
		$update = 0;
		foreach ($file as $key => $value) {
			if ($value[$k] == $val) {
				foreach ($array as $array_key => $array_value) {
					$update++;
					$file[$key][$array_key] = $array_value;
				}
			}
		}
		if (empty($update)) {
			return $update;
		}
		return $update;
	}

	/**
	 * 删除数据
	 * @param $k 指定的数据键
	 * @param $val 指定的数据键值
	 */
	public function delete($k, $val)
	{
		$file = $this->json_file();
		$delete = 0;
		foreach ($file as $key => $value) {
			if ($value[$k] == $val) {
				$delete++;
				unset($file[$key]);
			}
		}
		if (empty($delete)) {
			return false;
		}
		$this->array_file($file);
		return $delete;
	}

	/**
	 * 查询单条数据
	 * @param $k 指定的数据键
	 * @param $val 指定的数据键值
	 */
	public function find($k, $val)
	{
		$file = $this->json_file();
		foreach ($file as $key => $value) {
			if ($value[$k] == $val) {
				return $value;
			}
		}
		return false;
	}

	/**
	 * 查询多条数据
	 * @param $k 指定的数据键
	 * @param $val 指定的数据键值
	 */
	public function select($k, $val)
	{
		$file = $this->json_file();
		$data = [];
		foreach ($file as $key => $value) {
			if ($value[$k] == $val) {
				$select = true;
				$data[] = $value;
			}
		}
		if (!$select) {
			return false;
		}
		return $data;
	}

	public function json_encode($array)
	{
		return json_encode($array, 128 | 256);
	}

	public function json_file()
	{
		$data = json_decode(file_get_contents($this->data_path), true);
		if (is_array($data)) {
			return $data;
		} else {
			$this->DbError('文件格式错误！');
		}
	}

	private function array_file($array)
	{
		if (!is_array($array)) {
			$this->DbError('传入参数非数组！');
		}
		return file_put_contents($this->data_path, $this->json_encode($array));
	}

	private function DbError($msg)
	{
		exit('JsonDB error：' . $msg);
	}
}
?>
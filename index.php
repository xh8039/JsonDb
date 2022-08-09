<?php
include('./JsonDB.class.php');
$DB = new JsonDB('json_data');

// 添加数据
$data = [
	'a' => 5,
	'b' => "测试5"
];
$DB->insert($data);

// 删除数据
print_r($DB->delete('b', '测试3'));

// 更新数据
print_r($DB->update(['c' => '测试测试'], 'b', '测试4'));

// 查询单条数据
print_r($DB->find('b', '测试4'));

// 查询多条数据
print_r($DB->select('b', '测试4'));
<?php
include('./JsonDB.class.php');

// 自定义配置项 具体配置请参考文档：https://gitee.com/yh_IT/json-db/wikis

$optisons = [
	'data_type' => false, //关闭数据压缩 方便调试
];
$DB = new JsonDB($optisons);

// 添加单条数据
$DB->table('json_data')->insert([
	'a' => 5,
	'b' => "测试5"
]);

// 添加多条数据
$DB->table('json_data')->insertAll([
	[
		'a' => 5,
		'b' => "测试5"
	],
	[
		'c' => 1,
		'b' => "测试"
	]
]);

// 删除一行中的部分数据
$DB->table('json_data')->where('b', '测试3')->delete(['a', 'b']);

// 删除一行数据
$DB->table('json_data')->where('b', '测试3')->deleteAll();

// 更新数据
$DB->table('json_data')->where('b', '测试4')->update(['c' => '测试测试']);

// 根据ID查询数据
$DB->table('json_data')->where('id', 0)->find();

// 查询单条数据
$DB->table('json_data')->where('b', '测试')->find();

// 查询多条数据
$DB->table('json_data')->where('b', '测试4')->select();

// 查询所有数据
$DB->table('json_data')->selectAll();

// 自定义判断条件
$DB->table('json_data')->where('id', '>', 4)->select();

// 链式where
$DB->table('json_data')->where('id', 1)->where('a', 2)->select();
<?php
include('./JsonDB.class.php');

// 自定义配置项 具体配置请参考文档 https://gitee.com/yh_IT/json-db/wikis/自定义配置项
$optisons = [
	'data_type' => false //关闭数据压缩 方便调试
];
$DB = new JsonDB('json_data', $optisons);

// 添加数据
$DB->insert([
	'a' => 5,
	'b' => "测试5"
]);

// // 删除数据
$DB->where('b', '测试3')->delete();

// 更新数据
$DB->where('b', '测试4')->update(['c' => '测试测试']);

// 查询单条数据
$DB->where('b', '测试')->find();

// 查询多条数据
$DB->where('b', '测试4')->select();
?>
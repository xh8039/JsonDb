# JsonDB

#### 介绍
JsonDB 是一个由原生PHP实现的文件数据库，JsonDB只有一个文件，如果你不想使用庞大的数据库系统，或者一个站点内有多个小项目，那么JsonDB就是你最佳的选择。 JsonDB包括查询、添加、更新、删除等对数据的基本操作，适合存储数据量不大的数据

使用帮助文档：[gitee.com/yh_IT/json-db/wikis](https://gitee.com/yh_IT/json-db/wikis)

#### 软件架构
由纯原生PHP实现的Json文件数据库，将数据存储为Json格式，不占用MySql资源纯以读写文件的形式查询数据库，写法类似于ThinkPHP的查询。


#### 安装教程

```php
include('./JsonDB.class.php');
$DB = new JsonDB();
```


#### 使用说明

```php
<?php
include('./JsonDB.class.php');

// 自定义配置项 具体配置请参考文档 https://gitee.com/yh_IT/json-db/wikis/自定义配置项
$optisons = [
	'data_type' => false //关闭数据压缩 方便调试
];
$DB = new JsonDB($optisons);

// 添加数据
$DB->table('json_data')->insert([
	'a' => 5,
	'b' => "测试5"
]);

// // 删除数据
$DB->table('json_data')->where('b', '测试3')->delete();

// 更新数据
$DB->table('json_data')->where('b', '测试4')->update(['c' => '测试测试']);

// 查询单条数据
$DB->table('json_data')->where('b', '测试')->find();

// 查询多条数据
$DB->table('json_data')->where('b', '测试4')->select();
?>
```
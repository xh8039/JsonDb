# JsonDB

#### 介绍
JsonDB 是一个由原生PHP实现的文件数据库，JsonDB只有一个文件，如果你不想使用庞大的数据库系统，或者一个站点内有多个小项目，那么JsonDB就是你最佳的选择。 JsonDB包括查询、添加、更新、删除等对数据的基本操作，适合存储数据量不大的数据

使用帮助文档：[gitee.com/yh_IT/json-db/wikis](https://gitee.com/yh_IT/json-db/wikis)

#### 软件架构
由纯原生PHP实现的Json文件数据库，将数据存储为Json格式，不占用MySql资源纯以读写文件的形式查询数据库，写法类似于ThinkPHP的查询。


#### 安装教程

```php
include('./JsonDB.class.php');
$DB = new JsonDB('json_data');
```


#### 使用说明

```php
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
?>
```
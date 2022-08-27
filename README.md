# JsonDb

## 介绍
JsonDb 是一个由原生PHP实现的文件数据库，JsonDb只有一个文件，如果你不想使用庞大的数据库系统，或者一个站点内有多个小项目，那么JsonDb就是你最佳的选择。 JsonDb包括查询、添加、更新、删除等对数据的基本操作，适合存储数据量不大的数据

[使用帮助文档](https://gitee.com/yh_IT/json-db/wikis)

## 软件架构
由纯原生PHP实现的Json文件数据库，将数据存储为Json格式，不占用MySql资源纯以读写文件的形式查询数据库，写法类似于ThinkPHP的查询。

## 使用说明

### 使用 composer
```shell
composer require jsondb/jsondb
```
或者
### clone 项目到本地
- github地址
```shell
git clone https://github.com/xh8039/JsonDb.git
```
- 码云地址
```shell
git clone https://gitee.com/yh_IT/json-db.git
```

### 初始化
```php
require 'vendor/autoload.php';

use JsonDb\JsonDb\JsonDb;

// 自定义配置项 具体配置请参考文档：https://gitee.com/yh_IT/json-db/wikis
$optisons = [
	'data_type' => false, //关闭数据压缩 方便调试
];

$DB = new JsonDb($optisons);
```

### 插入单条数据 `insert`
```php
$DB->table('json_data')->insert([
	'a' => 5,
	'b' => "测试5"
]);
```

### 批量插入数据 `insertAll`
```php
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
```

### 删除一行中的部分数据 `delete`
```php
$DB->table('json_data')->where('b', '测试3')->delete(['a', 'b']);
```

### 删除整行数据 `deleteAll`
```php
$DB->table('json_data')->where('b', '测试3')->deleteAll();
```

### 更新数据 `update`
```php
$DB->table('json_data')->where('b', '测试4')->update(['c' => '测试测试']);
```

### 查询单条数据 `find`
```php
$DB->table('json_data')->where('b', '测试')->find();
```

### 查询多条数据 `select`
```php
$DB->table('json_data')->where('b', '测试4')->select();
```

### 查询表中所有数据 `selectAll`
```php
$DB->table('json_data')->selectAll();
```

### 根据ID查询数据
```php
$DB->table('json_data')->where('id', 0)->find();
```

### 字段 `LIKE` 查询
```php
$DB->table('json_data')->whereLike('b', '%测试')->select();
// 或者
$DB->table('json_data')->where('b', 'like', '%测试')->select();
```

### 自定义查询表达式
```php
$DB->table('json_data')->where('id', '>', 4)->select();
```

### 链式 `where`
```php
$DB->table('json_data')->where('id', 1)->where('a', 2)->select();
```

### 自定义判断条件
```php
$select = $DB->table('json_data')->where('`field_id` == 0 || `field_b` == `测试4`')->select();
```

### 限制结果数量 `limit`
```php
$DB->table('user')->where('status', 1)->limit(10)->select();
```

### 限制每次最大写入数量 `limit`
```php
$DB->table('user')->limit(100)->insertAll($userList);
```

### 设置表中的主键字段 `primaryKeyAdd`
```php
$DB->table('JsonDb')->primaryKeyAdd('user');
```

### 批量设置表中的主键字段 `primaryKeyAdd`
```php
$DB->table('JsonDb')->primaryKeyAdd(['name','uid']);
```

### 设置自动递增整数字段 `autoIncremeIntAdd`
```php
$DB->table('JsonDb')->autoIncremeIntAdd('uid');
```

### 批量设置自动递增整数字段 `autoIncremeIntAdd`
```php
$DB->table('JsonDb')->autoIncremeIntAdd(['pid','mid']);
```
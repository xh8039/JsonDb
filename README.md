# JsonDb使用文档

## 简介

JsonDb是一款由原生PHP实现的非关系型轻量级JSON文件数据库。如果你需要存储各种基础类的数据，或者一个站点内有多个小项目，那么JsonDb就是你最佳的选择。它包括查询、添加、更新、删除等对数据的基本操作，适合存储数据量不大的数据

[使用帮助文档](/readme/项目简介.md)丨[QQ交流群](https://jq.qq.com/?_wv=1027&k=k8ryssaa)：733120686

## 使用说明

### 使用 composer

```shell
composer require jsondb/jsondb:dev-master
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
// composer自动加载
require 'vendor/autoload.php';

use JsonDb\JsonDb\Db;

// 默认关闭数据压缩、加密并开启调试模式，可使用自定义配置
// 自定义配置项 具体配置请参考文档：https://gitee.com/yh_IT/json-db/wikis
$json_path = $_SERVER['DOCUMENT_ROOT'] . 'content' . DIRECTORY_SEPARATOR . 'JsonDb';
Db::setConfig([
 'path' => $json_path, // 数据存储路径（必须配置）
 'file_suffix' => '.json', // 文件后缀名
 'debug' => true, // 调试模式
 'encode' => null, // 数据加密函数
 'decode' => null, // 数据解密函数
]);
```

### 插入数据

#### 插入单条数据 `insert`

```php
DB::table('json_data')->insert([
 'a' => 5,
 'b' => "测试5"
]);
```

#### 批量插入数据 `insertAll`

```php
DB::table('json_data')->insertAll([
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

#### 限制每次最大写入数量 `limit`

```php
DB::table('user')->limit(100)->insertAll($userList);
```

### 删除数据

#### 删除一行中的部分数据 `delete`

```php
DB::table('json_data')->where('b', '测试3')->delete(['a', 'b']);
```

#### 删除整行数据 `delete`

```php
DB::table('json_data')->where('b', '测试3')->delete();
```

#### 删除整个表的数据 `delete`

```php
DB::table('json_data')->delete(true);
```

### 更新数据

#### 更新数据 `update`

```php
DB::table('json_data')->where('b', '测试4')->update(['c' => '测试测试']);
```

### 查询数据

#### 查询单条数据 `find`

```php
DB::table('json_data')->where('b', '测试')->find();
```

#### 查询多条数据 `select`

```php
DB::table('json_data')->where('b', '测试4')->select();
```

#### 查询表中所有数据 `selectAll`

```php
DB::table('json_data')->selectAll();
```

#### 根据ID查询数据

```php
DB::table('json_data')->where('id', 0)->find();
```

#### 字段 `LIKE` 查询

```php
DB::table('json_data')->whereLike('b', '%测试')->select();
```

#### 自定义查询表达式

```php
DB::table('json_data')->where('id', '>', 4)->select();
```

#### 链式 `where`

```php
DB::table('json_data')->where('id', 1)->where('a', 2)->select();
```

#### 限制结果数量 `limit`

```php
DB::table('user')->where('status', 1)->limit(10)->select();
```

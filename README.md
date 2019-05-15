# fjredis #

这是一个轻量的PHP操作redis的库，使用简单，没有使用C扩展

## 下载

使用composer进行下载

```
composer require flagupdown/fjredis
```

下载成功之后,在开始写代码的文件中包含composer自动加载器

```
require 'vendor/autoload.php';
```

## 使用

```php
<?php
namespace test;
use FlagUpDown\FjRedis;
require "vendor/autoload.php";

$redis = new FjRedis();
// $redis->stream_set_timeout(2); // 设置连接超时的时间
$redis->get('key'); // bool(false)
$redis->set('key','value'); // bool(true)
$redis->get('key'); // string(5) "value"
$redis->rpush('list', array('value1', 'value2', 'value3')); // int(3)
$redis->lrange('list', 0, -1); // ['value1', 'value2', 'value3']
$redis->flushall(); // bool(true)
```

### 批量操作（pipeline）

将一个或多个命令先预存到本地，最后一次性发送，从而达到减少IO调用的次数的目的。

本库默认缓存区的大小是无限的。

```php
$redis = new FjRedis();

//开启pipline
$redis->pipeline_start();
for ($i = 0;$i < 10000;$i++) {
    $redis->rpush('mylist', array('1value' . $i, '2value' . $i, '3value' . $i));
}
//发送命令，得到命令返回值，关闭pipline
var_dump($redis->pipeline_end());
var_dump($redis->llen('mylist'));

//开启pipline
$redis->pipeline_start();
$redis->set("key1","value1");
//丢弃缓冲区中的命令，关闭pipeline
$redis->pipeline_discard();

//开启pipline
$redis->pipeline_start();
$redis->set("key1","value2");
//清空pipline中缓存的命令
$redis->pipeline_rollback();
$redis->set("key1","value3");
//发送命令，得到命令返回值，关闭pipline
var_dump($redis->pipeline_end());
var_dump($redis->get("key1"));

$redis->flushall();
```

### scan,hscan,zscan,sscan迭代器

+ SCAN命令用于迭代当前数据库中的数据库键。
+ SSCAN命令用于迭代集合键中的元素。
+ HSCAN命令用于迭代哈希键中的键值对。
+ ZSCAN命令用于迭代有序集合中的元素（包括元素成员和元素分值）

```php
$redis = new FjRedis();

//定义一些数据库键
for ($i = 0;$i < 10;$i++) {
    $redis->set('key_' . $i, 'value_' . $i);
}

//迭代当前数据库中的数据库键
foreach ($redis->iterator()->scan() as $key => $value) {
    echo $key,'=>',$value,"\n";
}

//定义一个集合键
for ($i = 0; $i < 100; $i++) {
    $redis->sadd('mySet', "value-{$i}");
}

//迭代集合键中给定模式相匹配的元素
foreach ($redis->iterator()->sscan('mySet', 'value-1*') as $key => $value) {
    echo $key,'=>',$value,"\n";
}

//定义一个哈希键
for ($i = 0; $i < 100; $i++) {
    $redis->hset('myHset', "key:{$i}", "value:{$i}");
}

//迭代哈希键中的键值对(键与给定模式相匹配)
foreach ($redis->iterator()->hscan('myHset', 'key:1*') as $key => $value) {
    echo $key,'=>',$value,"\n";
}

//定义一个有序集合键
for ($i = 0; $i < 100; $i++) {
    $redis->zadd('sortedSet', $i, "value{{$i}}");
}

//迭代有序集合中的元素（包括元素成员和元素分值）
foreach ($redis->iterator()->zscan('sortedSet', 'value{1*}', 100) as $key => $value) {
    echo $key,'=>value:',$value[0],',score:',$value[1],"\n";
}
```


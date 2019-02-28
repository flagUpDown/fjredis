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
// $redis->stream_set_timeout(2);
var_dump($redis->set('key','value'));
var_dump($redis->get('key'));
var_dump($redis->rpush('list', array('value1', 'value2', 'value3')));
var_dump($redis->lrange('list', 0, -1));
var_dump($redis->flushall());
```

运行结果：

```
string(2) "OK"
string(5) "value"
int(3)
array(3) {
  [0]=>
  string(6) "value1"
  [1]=>
  string(6) "value2"
  [2]=>
  string(6) "value3"
}
string(2) "OK"
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
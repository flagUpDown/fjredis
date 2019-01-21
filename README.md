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


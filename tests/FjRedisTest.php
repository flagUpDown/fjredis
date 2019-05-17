<?php
namespace FlagUpDown\Tests;

use FlagUpDown\FjRedis;
use PHPUnit\Framework\TestCase;

require 'vendor/autoload.php';

error_reporting(E_ALL);

class FjRedisTest extends TestCase
{
    protected $fjRedis;

    protected function setUp()
    {
        $this->fjRedis = new FjRedis();
        $this->fjRedis->flushall();
    }

    protected function tearDown()
    {
        $this->fjRedis->close();
    }

    public function testFlush()
    {
        $this->fjRedis->set('foo', 'FOO');
        $this->assertTrue($this->fjRedis->flushall());
        $this->assertFalse($this->fjRedis->get('foo'));
    }

    public function testScalars()
    {
        // Basic get/set
        $this->assertTrue($this->fjRedis->set('foo', 'FOO'));
        $this->assertEquals('FOO', $this->fjRedis->get('foo'));
        $this->assertFalse($this->fjRedis->get('nil'));

        // exists support
        $this->assertEquals($this->fjRedis->exists('foo'), 1);
        $this->assertEquals($this->fjRedis->exists('nil'), 0);

        // Empty string
        $this->fjRedis->set('empty', '');
        $this->assertEquals('', $this->fjRedis->get('empty'));

        // UTF-8 characters
        $utf8str = str_repeat('quarter: ¼, micro: µ, thorn: Þ, ', 500);
        $this->fjRedis->set('utf8', $utf8str);
        $this->assertEquals($utf8str, $this->fjRedis->get('utf8'));

        // Array
        $this->assertTrue($this->fjRedis->mSet(['foo' => 'Foo', 'bar' => 'Bar']));
        $mGet = $this->fjRedis->mGet(['foo', 'bar', 'empty']);
        $this->assertEquals(['Foo', 'Bar', ''], $mGet);

        // Non-array
        $mGet = $this->fjRedis->mGet('foo', 'bar');
        $this->assertEquals(['Foo', 'Bar'], $mGet);

        // Delete strings, null response
        $this->assertEquals(2, $this->fjRedis->del('foo', 'bar'));
        $this->assertFalse($this->fjRedis->get('foo'));
        $this->assertFalse($this->fjRedis->get('bar'));

        // Long string
        $longString = str_repeat(md5('asd'), 4096); // 128k (redis.h REDIS_INLINE_MAX_SIZE = 64k)
        $this->assertTrue($this->fjRedis->set('long', $longString));
        $this->assertEquals($longString, $this->fjRedis->get('long'));
    }

    public function testSets()
    {
        // Multiple arguments
        $this->assertEquals(2, $this->fjRedis->sAdd('myset', 'Hello', 'World'));

        // Array Arguments
        $mysetList = ['Hello', 'Cruel', 'Cruel'];
        $this->assertEquals(1, $this->fjRedis->sAdd('myset', $mysetList));

        // Non-empty set
        $members = $this->fjRedis->sMembers('myset');
        $this->assertCount(3, $members);
        foreach ($mysetList as $set) {
            $this->assertTrue(in_array($set, $members));
        }

        // Empty set
        $this->assertEquals([], $this->fjRedis->sMembers('noexist'));
    }

    public function testSortedSets()
    {
        $this->assertEquals(1, $this->fjRedis->zAdd('myset', 1, 'Hello'));
        $this->assertEquals(1, $this->fjRedis->zAdd('myset', 2.123, 'World'));
        $this->assertEquals(1, $this->fjRedis->zAdd('myset', 10, 'And'));
        $this->assertEquals(1, $this->fjRedis->zAdd('myset', 11, 'Goodbye'));

        $range = $this->fjRedis->zRange('myset', 1, 2);
        $this->assertEquals(['World', 'And'], $range);

        $range = $this->fjRedis->zRange('myset', 1, 2, ['withscores' => true]);
        $this->assertEquals(['World', 2.123, 'And', 10], $range);

        // withscores-option is off
        $range = $this->fjRedis->zRange('myset', 0, 4, ['withscores']);
        $this->assertEquals(['Hello', 1, 'World', 2.123, 'And', 10, 'Goodbye', 11], $range);


        $range = $this->fjRedis->zRange('myset', 0, 4, ['withscores' => false]);
        $this->assertEquals(['Hello', 'World', 'And', 'Goodbye'], $range);

        $range = $this->fjRedis->zRevRange('myset', 0, 1, ['withscores' => true]);
        $this->assertEquals(['Goodbye', 11, 'And', 10], $range);

        $range = $this->fjRedis->zRangeByScore('myset', '-inf', '+inf');
        $this->assertEquals(['Hello', 'World', 'And', 'Goodbye'], $range);

        $range = $this->fjRedis->zRangeByScore('myset', '-inf', '+inf', ['limit' => [1, 2]]);
        $this->assertEquals(['World', 'And'], $range);

        $range = $this->fjRedis->zRangeByScore('myset', '-inf', '+inf', ['withscores' => true, 'limit' => [1, 2]]);
        $this->assertEquals(['World', 2.123, 'And', 10], $range);
    }
}

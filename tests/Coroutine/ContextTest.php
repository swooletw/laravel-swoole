<?php

namespace SwooleTW\Http\Tests\Coroutine;

use Illuminate\Container\Container;
use Mockery as m;
use SwooleTW\Http\Coroutine\Context;
use SwooleTW\Http\Tests\TestCase;
use TypeError;

class ContextTest extends TestCase
{
    public function testGetCoroutineId()
    {
        $this->assertSame(-1, Context::getCoroutineId());
    }

    public function testSetApp()
    {
        $this->expectException(TypeError::class);
        Context::setApp('foo');

        $container = m::mock(Container::class);
        $this->assertSame($container, Context::getApp());
    }

    public function testSetData()
    {
        $this->expectException(TypeError::class);
        Context::setData(new \stdClass, 'foo');

        Context::setData('foo', 'bar');
        $this->assertSame('bar', Context::getData('foo'));
        $this->assertNull(Context::getData('bar'));
    }

    public function testRemoveData()
    {
        Context::setData('foo', 'bar');
        Context::setData('sea', 'food');

        Context::removeData('foo');
        $this->assertSame('food', Context::getData('sea'));
        $this->assertNull(Context::getData('foo'));
    }

    public function testGetDataKeys()
    {
        Context::setData('foo', 'bar');
        Context::setData('sea', 'food');

        $this->assertSame(['foo', 'sea'], Context::getDataKeys());
    }

    public function testGetDataKeyInCoroutine()
    {
        $data1 = null;
        $data2 = null;
        $data3 = null;

        $coroutineId1 = null;
        $coroutineId2 = null;
        $coroutineId3 = null;

        \Swoole\Coroutine\run(function () use (&$data1, &$data2, &$data3, &$coroutineId1, &$coroutineId2, &$coroutineId3) {
            Context::setData('foo', 'bar');

            $data1 = Context::getData('foo');
            $data2 = 'baz';
            $data2 = 'swoole';

            $coroutineId1 = Context::getRequestedCoroutineId();
            $coroutineId2 = -1;
            $coroutineId3 = -1;

            go(function () use (&$data2, &$data3, &$coroutineId2, &$coroutineId3) {
                $data2 = Context::getData('foo');
                $coroutineId2 = Context::getRequestedCoroutineId();

                // test nested coroutine
                go(function () use (&$data3, &$coroutineId3) {
                    $data3 = Context::getData('foo');
                    $coroutineId3 = Context::getRequestedCoroutineId();
                });
            });
        });

        $this->assertSame('bar', $data1);
        $this->assertSame($data1, $data2);
        $this->assertSame($data2, $data3);
        $this->assertSame($coroutineId1, $coroutineId2);
        $this->assertSame($coroutineId2, $coroutineId3);

    }

    public function testClear()
    {
        Context::setApp(m::mock(Container::class));
        Context::setData('foo', 'bar');

        Context::clear();
        $this->assertNull(Context::getApp());
        $this->assertSame([], Context::getDataKeys());
    }
}

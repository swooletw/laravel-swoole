<?php

namespace SwooleTW\Http\Tests\SocketIO;

use Mockery as m;
use Swoole\Websocket\Frame;
use Illuminate\Http\Request;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;

class WebsocketHandlerTest extends TestCase
{
    public function testOnOpen()
    {
        $fd = 1;
        $request = m::mock(Request::class);
        $request->shouldReceive('input')
            ->with('sid')
            ->once()
            ->andReturn(false);

        Config::shouldReceive('get')
            ->with('swoole_websocket.ping_interval')
            ->once();
        Config::shouldReceive('get')
            ->with('swoole_websocket.ping_timeout')
            ->once();

        $jsonEncode = false;
        $this->mockMethod('json_encode', function () use (&$jsonEncode) {
            $jsonEncode = true;
            return '{foo: "bar"}';
        }, 'SwooleTW\Http\Websocket\SocketIO');

        App::shouldReceive('make')
            ->with('swoole.server')
            ->twice()
            ->andReturnSelf();
        App::shouldReceive('push')
            ->with($fd, '0{foo: "bar"}')
            ->once();
        App::shouldReceive('push')
            ->with($fd, '40')
            ->once();

        $handler = new WebsocketHandler;
        $this->assertTrue($handler->onOpen($fd, $request));
        $this->assertTrue($jsonEncode);
    }

    public function testOnOpenWithFalseReturn()
    {
        $fd = 1;
        $request = m::mock(Request::class);
        $request->shouldReceive('input')
            ->with('sid')
            ->once()
            ->andReturn(true);

        $handler = new WebsocketHandler;
        $this->assertFalse($handler->onOpen($fd, $request));
    }

    public function testOnMessage()
    {
        $handler = new WebsocketHandler;
        $this->assertNull($handler->onMessage(new Frame));
    }

    public function testOnClose()
    {
        $handler = new WebsocketHandler;
        $this->assertNull($handler->onClose(0, 0));
    }
}
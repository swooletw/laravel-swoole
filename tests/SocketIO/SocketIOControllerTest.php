<?php

namespace SwooleTW\Http\Tests\SocketIO;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Mockery as m;
use SwooleTW\Http\Controllers\SocketIOController;
use SwooleTW\Http\Tests\TestCase;

class SocketIOControllerTest extends TestCase
{
    public function testUnknownTransport()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('input')
                ->with('transport')
                ->once()
                ->andReturn('foo');

        $this->mockMethod('response', function () {
            $response = m::mock('response');
            $response->shouldReceive('json')
                     ->once()
                     ->with([
                         'code' => 0,
                         'message' => 'Transport unknown',
                     ], 400);

            return $response;
        });

        $controller = new SocketIOController;
        $controller->upgrade($request);
    }

    public function testHasSid()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('input')
                ->with('transport')
                ->once()
                ->andReturn('websocket');
        $request->shouldReceive('has')
                ->with('sid')
                ->once()
                ->andReturn(true);

        $controller = new SocketIOController;
        $result = $controller->upgrade($request);

        $this->assertSame('1:6', $result);
    }

    public function testUpgradePayload()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('input')
                ->with('transport')
                ->once()
                ->andReturn('websocket');
        $request->shouldReceive('has')
                ->with('sid')
                ->once()
                ->andReturn(false);

        $base64Encode = false;
        $this->mockMethod('base64_encode', function () use (&$base64Encode) {
            $base64Encode = true;

            return 'payload';
        });

        Config::shouldReceive('get')
              ->with('swoole_websocket.ping_interval')
              ->once()
              ->andReturn(1);
        Config::shouldReceive('get')
              ->with('swoole_websocket.ping_timeout')
              ->once()
              ->andReturn(1);

        $expectedPayload = json_encode([
            'sid' => 'payload',
            'upgrades' => ['websocket'],
            'pingInterval' => 1,
            'pingTimeout' => 1,
        ]);
        $expectedPayload = '97:0' . $expectedPayload . '2:40';

        $controller = new SocketIOController;
        $result = $controller->upgrade($request);

        $this->assertTrue($base64Encode);
        $this->assertSame($expectedPayload, $result);
    }

    public function testReject()
    {
        $this->mockMethod('response', function () {
            $response = m::mock('response');
            $response->shouldReceive('json')
                     ->once()
                     ->with([
                         'code' => 3,
                         'message' => 'Bad request',
                     ], 400);

            return $response;
        });

        $controller = new SocketIOController;
        $controller->reject();
    }

    protected function mockMethod($name, \Closure $function, $namespace = null)
    {
        parent::mockMethod($name, $function, 'SwooleTW\Http\Controllers');
    }
}
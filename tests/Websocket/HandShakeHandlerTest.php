<?php

namespace SwooleTW\Http\Tests\Websocket;

use Mockery as m;
use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\HandShakeHandler;

class HandShakeHandlerTest extends TestCase
{
    public function testHandle()
    {
        // arrange
        $request = m::mock(Request::class);
        $request->header['sec-websocket-key'] = 'Bet8DkPFq9ZxvIBvPcNy1A==';

        $response = m::mock(Response::class);
        $response->shouldReceive('header')->withAnyArgs()->times(4)->andReturnSelf();
        $response->shouldReceive('status')->with(101)->once()->andReturnSelf();
        $response->shouldReceive('end')->withAnyArgs()->once()->andReturnSelf();

        $handler = new HandShakeHandler;

        // act
        $actual = $handler->handle($request, $response);

        // assert
        $this->assertTrue($actual);
    }

    public function testHandleReturnFalse()
    {
        // arrange
        $request = m::mock(Request::class);
        $request->header['sec-websocket-key'] = 'test';

        $response = m::mock(Response::class);
        $response->shouldReceive('end')->withAnyArgs()->once()->andReturnSelf();

        $handler = new HandShakeHandler;

        // act
        $actual = $handler->handle($request, $response);

        // assert
        $this->assertFalse($actual);
    }

    public function testHandleWithSecWebsocketProtocol()
    {
        // arrange
        $request = m::mock(Request::class);
        $request->header['sec-websocket-key'] = 'Bet8DkPFq9ZxvIBvPcNy1A==';
        $request->header['sec-websocket-protocol'] = 'graphql-ws';

        $response = m::mock(Response::class);
        $response->shouldReceive('header')->withAnyArgs()->times(5)->andReturnSelf();
        $response->shouldReceive('status')->with(101)->once()->andReturnSelf();
        $response->shouldReceive('end')->withAnyArgs()->once()->andReturnSelf();

        $handler = new HandShakeHandler;

        // act
        $actual = $handler->handle($request, $response);

        // assert
        $this->assertTrue($actual);
    }
}

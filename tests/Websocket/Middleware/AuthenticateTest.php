<?php

namespace SwooleTW\Http\Tests\Websocket\Middleware;

use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\Middleware\Authenticate;

class AuthenticateTest extends TestCase
{
    public function testAuthenticate()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('setUserResolver')
                ->once();

        $auth = m::mock(Auth::class);
        $auth->shouldReceive('authenticate')
             ->once()
             ->andReturn('user');
        $auth->shouldReceive('setRequest')
            ->with($request)
            ->once();

        $middleware = new Authenticate($auth);
        $middleware->handle($request, function ($next) {
            return $next;
        });
    }
}
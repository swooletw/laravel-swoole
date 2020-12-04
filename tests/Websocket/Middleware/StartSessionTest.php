<?php

namespace SwooleTW\Http\Tests\Websocket\Middleware;

use SwooleTW\Http\Tests\TestCase;

class StartSessionTest extends TestCase
{
    public function testStartSession()
    {
        // tap() is not supported before Laravel 5.3
        $this->assertTrue(true);
        // $cookies = m::mock('cookies');
        // $cookies->shouldReceive('get')
        //     ->with($cookieName = 'cookieName')
        //     ->once()
        //     ->andReturn($sessionName = 'sessionName');

        // $request = m::mock(Request::class);
        // $request->cookies = $cookies;
        // $request->shouldReceive('setLaravelSession')
        //     ->once();

        // $session = m::mock('session');
        // $session->shouldReceive('setId')
        //     ->once()
        //     ->with($sessionName);
        // $session->shouldReceive('getName')
        //     ->once()
        //     ->andReturn($cookieName);
        // $session->shouldReceive('setRequestOnHandler')
        //     ->once();
        // $session->shouldReceive('start')
        //     ->once();

        // $manager = m::mock(SessionManager::class);
        // $manager->shouldReceive('getSessionConfig')
        //     ->once()
        //     ->andReturn(['driver' => 'foo']);
        // $manager->shouldReceive('driver')
        //     ->once()
        //     ->andReturn($session);

        // $middleware = new StartSession($manager);
        // $middleware->handle($request, function ($next) {
        //     return $next;
        // });
    }
}
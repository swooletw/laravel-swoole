<?php

namespace SwooleTW\Http\Tests\Server\Resetters;

use Mockery as m;
use Illuminate\Http\Request;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Server\Sandbox;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\Resetters\BindRequest;

class BindRequestTest extends TestCase
{
    public function testBindRequest()
    {
        $request = m::mock(Request::class);

        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('getRequest')
                ->once()
                ->andReturn($request);

        $container = new Container;

        $resetter = new BindRequest;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($request, $app->make('request'));
    }
}

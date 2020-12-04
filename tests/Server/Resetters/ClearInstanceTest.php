<?php

namespace SwooleTW\Http\Tests\Server\Resetters;

use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Server\Sandbox;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\Resetters\ClearInstances;

class ClearInstanceTest extends TestCase
{
    public function testClearInstance()
    {
        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('getConfig->get')
                ->with('swoole_http.instances', [])
                ->once()
                ->andReturn(['foo']);

        $container = new Container;
        $container->instance('foo', m::mock('foo'));

        $resetter = new ClearInstances;
        $app = $resetter->handle($container, $sandbox);

        $this->assertFalse($app->bound('foo'));
    }
}

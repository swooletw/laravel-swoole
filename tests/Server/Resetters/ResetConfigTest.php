<?php

namespace SwooleTW\Http\Tests\Server\Resetters;

use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Server\Sandbox;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\Resetters\ResetConfig;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class ResetConfigTest extends TestCase
{
    public function testResetConfig()
    {
        $config = m::mock(ConfigContract::class);
        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('getConfig')
                ->once()
                ->andReturn($config);

        $container = new Container;
        $resetter = new ResetConfig;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame(get_class($config), get_class($app->make('config')));
    }
}

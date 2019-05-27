<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\PidManager;
use SwooleTW\Http\Server\PidManagerFactory;
use SwooleTW\Http\Tests\TestCase;

class PidManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsAPidManager()
    {
        $factory = new PidManagerFactory($container = new Container);

        $config = m::mock(ConfigContract::class);

        $container->singleton(ConfigContract::class, function () use ($config) {
            return $config;
        });

        $container->alias(ConfigContract::class, 'config');

        $this->assertInstanceOf(PidManager::class, $factory());
    }
}

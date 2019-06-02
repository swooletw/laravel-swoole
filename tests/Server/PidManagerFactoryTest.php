<?php

namespace SwooleTW\Http\Tests\Server;

use Illuminate\Container\Container;
use Mockery as m;
use SwooleTW\Http\Server\PidManager;
use SwooleTW\Http\Server\PidManagerFactory;
use SwooleTW\Http\Tests\TestCase;

class PidManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsAPidManager()
    {
        $factory = new PidManagerFactory($container = new Container);

        $config = $this->getConfig();

        $container->singleton(ConfigContract::class, function () use ($config) {
            return $config;
        });

        $container->alias(ConfigContract::class, 'config');

        $this->assertInstanceOf(PidManager::class, $factory($container));
    }

    protected function getConfig()
    {
        $config = m::mock(ConfigContract::class);
        $callback = function ($key) {
            return $this->config[$key] ?? '';
        };

        $config->shouldReceive('get')
               ->with(m::type('string'), m::any())
               ->andReturnUsing($callback);
        $config->shouldReceive('get')
               ->with(m::type('string'))
               ->andReturnUsing($callback);

        return $config;
    }
}

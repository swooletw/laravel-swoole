<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use Swoole\Http\Server as HttpServer;

class ManagerTest extends TestCase
{
    protected $config = [
        'swoole_http.tables' => [],
    ];

    public function testRun()
    {
        $server = $this->getServer();
        $server->shouldReceive('start')->once();

        $container = $this->getContainer($server);
        $manager = $this->getManager($container);
        $manager->run();
    }

    public function testStop()
    {
        $server = $this->getServer();
        $server->shouldReceive('shutdown')->once();

        $container = $this->getContainer($server);
        $manager = $this->getManager($container);
        $manager->stop();
    }

    protected function getManager($container = null, $framework = 'laravel', $path = '/')
    {
        return new Manager($container ?? $this->getContainer(), $framework, $path);
    }

    protected function getContainer($server = null, $config = null)
    {
        $server = $server ?? $this->getServer();
        $config = $config ?? $this->getConfig();
        $container = new Container;

        $container->singleton('config', function () use ($config) {
            return $config;
        });
        $container->singleton('swoole.server', function () use ($server) {
            return $server;
        });

        return $container;
    }

    protected function getServer()
    {
        $server = m::mock('server');
        $server->shouldReceive('on');
        $server->master_pid = -1;

        return $server;
    }

    protected function getConfig()
    {
        $config = m::mock('config');
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

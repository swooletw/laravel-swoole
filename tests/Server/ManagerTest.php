<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use Swoole\Http\Server as HttpServer;
use SwooleTW\Http\Websocket\Rooms\TableRoom;
use SwooleTW\Http\Websocket\SocketIO\SocketIOParser;

class ManagerTest extends TestCase
{
    protected $config = [
        'swoole_http.websocket.enabled' => false,
        'swoole_http.tables' => [],
    ];

    protected $websocketConfig = [
        'swoole_http.websocket.enabled' => true,
        'swoole_websocket.parser' => SocketIOParser::class,
        'swoole_websocket.default' => 'table',
        'swoole_websocket.settings.table' => [
            'room_rows' => 10,
            'room_size' => 10,
            'client_rows' => 10,
            'client_size' => 10
        ],
        'swoole_websocket.drivers.table' => TableRoom::class,
        'swoole_http.tables' => [],
    ];

    public function testGetFramework()
    {
        $manager = $this->getManager();
        $this->assertSame('laravel', $manager->getFramework());
    }

    public function testGetBasePath()
    {
        $manager = $this->getManager();
        $this->assertSame('/', $manager->getBasePath());
    }

    public function testGetWebsocketParser()
    {
        $manager = $this->getWebsocketManager();

        $this->assertTrue($manager->getParser() instanceof SocketIOParser);
    }

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

    // public function testOnStart()
    // {
    //     //
    // }

    protected function getManager($container = null, $framework = 'laravel', $path = '/')
    {
        return new Manager($container ?? $this->getContainer(), $framework, $path);
    }

    protected function getWebsocketManager()
    {
        $container = $this->getContainer($this->getServer(), $this->getConfig(true));

        return $this->getManager($container);
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

    protected function getConfig($websocket = false)
    {
        $config = m::mock('config');
        $settings = $websocket ? 'websocketConfig' : 'config';
        $callback = function ($key) use ($settings) {
            return $this->$settings[$key] ?? '';
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

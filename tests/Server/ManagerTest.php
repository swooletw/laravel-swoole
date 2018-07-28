<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use phpmock\MockBuilder;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use SwooleTW\Http\Table\SwooleTable;
use Swoole\Http\Server as HttpServer;
use Illuminate\Support\Facades\Config;
use SwooleTW\Http\Websocket\Websocket;
use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleTW\Http\Websocket\Rooms\TableRoom;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use SwooleTW\Http\Websocket\SocketIO\SocketIOParser;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;
use SwooleTW\Http\Websocket\Facades\Websocket as WebsocketFacade;

class ManagerTest extends TestCase
{
    protected $config = [
        'swoole_http.websocket.enabled' => false,
        'swoole_http.tables' => [],
        'swoole_http.providers' => []
    ];

    protected $websocketConfig = [
        'swoole_http.websocket.enabled' => true,
        'swoole_websocket.parser' => SocketIOParser::class,
        'swoole_websocket.handler' => WebsocketHandler::class,
        'swoole_websocket.default' => 'table',
        'swoole_websocket.settings.table' => [
            'room_rows' => 10,
            'room_size' => 10,
            'client_rows' => 10,
            'client_size' => 10
        ],
        'swoole_websocket.drivers.table' => TableRoom::class,
        'swoole_http.tables' => [],
        'swoole_http.providers' => [],
        'swoole_http.server.public_path' => '/'
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

    public function testOnStart()
    {
        $filePutContents = false;
        $this->mockMethod('file_put_contents', function () use (&$filePutContents) {
            $filePutContents = true;
        });

        $container = $this->getContainer();
        $container->singleton('events', function () {
            return $this->getEvent('swoole.start');
        });
        $manager = $this->getManager($container);
        $manager->onStart();

        $this->assertTrue($filePutContents);
    }

    public function testOnManagerStart()
    {
        $container = $this->getContainer();
        $container->singleton('events', function () {
            return $this->getEvent('swoole.managerStart');
        });
        $manager = $this->getManager($container);
        $manager->onManagerStart();
    }

    public function testOnWorkerStart()
    {
        $server = $this->getServer();
        $manager = $this->getManager();

        $container = $this->getContainer($this->getServer(), $this->getConfig(true));
        $container->singleton('events', function () {
            return $this->getEvent('swoole.workerStart');
        });

        Config::shouldReceive('get')
            ->with('swoole_websocket.middleware', [])
            ->once();
        WebsocketFacade::shouldReceive('on')->times(3);

        $manager = $this->getWebsocketManager($container);
        $manager->setApplication($container);
        $manager->onWorkerStart($server);

        $app = $manager->getApplication();
        $this->assertTrue($app->make('swoole.sandbox') instanceof Sandbox);
        $this->assertTrue($app->make('swoole.table') instanceof SwooleTable);
        $this->assertTrue($app->make('swoole.room') instanceof RoomContract);
        $this->assertTrue($app->make('swoole.websocket') instanceof Websocket);
    }

    public function testOnRequest()
    {
        $server = $this->getServer();
        $manager = $this->getManager();

        $container = $this->getContainer($this->getServer(), $this->getConfig(true));
        $container->singleton('events', function () {
            return $this->getEvent('swoole.request', false);
        });
        $container->singleton('swoole.websocket', function () {
            $websocket = m::mock(Websocket::class);
            $websocket->shouldReceive('reset')
                ->with(true)
                ->once();
            return $websocket;
        });
        $container->singleton('swoole.sandbox', function () {
            $sandbox = m::mock(Sandbox::class);
            $sandbox->shouldReceive('setRequest')
                ->with(m::type('Illuminate\Http\Request'))
                ->once();
            $sandbox->shouldReceive('enable')
                ->once();
            $sandbox->shouldReceive('run')
                ->with(m::type('Illuminate\Http\Request'))
                ->once();
            $sandbox->shouldReceive('disable')
                ->once();
            return $sandbox;
        });

        $this->mockMethod('base_path', function () {
            return '/';
        });

        $request = m::mock(Request::class);
        $request->shouldReceive('rawcontent')
            ->once()
            ->andReturn([]);

        $response = m::mock(Response::class);
        $response->shouldReceive('header')
            ->twice()
            ->andReturnSelf();
        $response->shouldReceive('status')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('end')
            ->once()
            ->andReturnSelf();

        $manager = $this->getWebsocketManager($container);
        $manager->setApplication($container);
        $manager->onRequest($request, $response);
    }

    protected function getManager($container = null, $framework = 'laravel', $path = '/')
    {
        return new Manager($container ?: $this->getContainer(), $framework, $path);
    }

    protected function getWebsocketManager($container = null)
    {
        return $this->getManager($container ?: $this->getContainer($this->getServer(), $this->getConfig(true)));
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
        $server->taskworker = false;
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

    protected function getEvent($name, $default = true)
    {
        $event = m::mock('event')
            ->shouldReceive('fire')
            ->with($name, m::any())
            ->once();

        $event = $default ? $event->with($name, m::any()) : $event->with($name);

        return $event->getMock();
    }

    protected function mockMethod($name, \Closure $function, $namespace = null)
    {
        $builder = new MockBuilder();
        $builder->setNamespace($namespace ?: 'SwooleTW\Http\Server')
                ->setName($name)
                ->setFunction($function);

        $mock = $builder->build();
        $mock->enable();
    }
}

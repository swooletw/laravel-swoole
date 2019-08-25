<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use Swoole\Table;
use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use SwooleTW\Http\Websocket\HandShakeHandler;
use SwooleTW\Http\Websocket\Parser;
use SwooleTW\Http\Server\PidManager;
use SwooleTW\Http\Table\SwooleTable;
use Laravel\Lumen\Exceptions\Handler;
use Illuminate\Support\Facades\Config;
use SwooleTW\Http\Websocket\Websocket;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\HandlerContract;
use SwooleTW\Http\Websocket\Rooms\TableRoom;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use SwooleTW\Http\Websocket\SocketIO\SocketIOParser;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use SwooleTW\Http\Websocket\Facades\Websocket as WebsocketFacade;

class ManagerTest extends TestCase
{
    protected $config = [
        'swoole_http.websocket.enabled' => false,
        'swoole_http.tables' => [
            'table_name' => [
                'size' => 1024,
                'columns' => [
                    ['name' => 'column_name', 'type' => Table::TYPE_STRING, 'size' => 1024],
                ],
            ],
        ],
        'swoole_http.providers' => [],
        'swoole_http.resetters' => [],
        'swoole_http.pre_resolved' => ['foo'],
    ];

    protected $websocketConfig = [
        'swoole_http.websocket.enabled' => true,
        'swoole_websocket.parser' => SocketIOParser::class,
        'swoole_websocket.handler' => WebsocketHandler::class,
        'swoole_websocket.handshake.enabled' => true,
        'swoole_websocket.handshake.handler' => HandShakeHandler::class,
        'swoole_websocket.default' => 'table',
        'swoole_websocket.settings.table' => [
            'room_rows' => 10,
            'room_size' => 10,
            'client_rows' => 10,
            'client_size' => 10,
        ],
        'swoole_websocket.drivers.table' => TableRoom::class,
        'swoole_http.tables' => [],
        'swoole_http.providers' => [],
        'swoole_http.resetters' => [],
        'swoole_http.server.public_path' => '/',
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

        $this->assertTrue($manager->getPayloadParser() instanceof SocketIOParser);
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
        $pidManager = m::mock(PidManager::class);
        $pidManager->shouldReceive('write')->once();

        $container = $this->getContainer();
        $container->singleton('events', function () {
            return $this->getEvent('swoole.start');
        });
        $container->singleton(PidManager::class, function () use ($pidManager) {
            return $pidManager;
        });
        $manager = $this->getManager($container);
        $manager->onStart();
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
        $this->getManager();

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
        $this->assertTrue($app->make(Sandbox::class) instanceof Sandbox);
        $this->assertTrue($app->make('swoole.table') instanceof SwooleTable);
        $this->assertTrue($app->make('swoole.room') instanceof RoomContract);
        $this->assertTrue($app->make(Websocket::class) instanceof Websocket);
    }

    public function testLoadApplication()
    {
        $server = $this->getServer();
        $container = $this->getContainer($server, $this->getConfig());
        $container->singleton('events', function () {
            return $this->getEvent('swoole.workerStart');
        });

        $path = __DIR__ . '/../fixtures';
        $manager = $this->getManager($container, $framework = 'laravel', $path);
        $manager->onWorkerStart($server);
    }

    public function testOnTaskWorkerStart()
    {
        $server = $this->getServer();
        $server->taskworker = true;

        $container = $this->getContainer($server);
        $container->singleton('events', function () {
            return $this->getEvent('swoole.workerStart');
        });

        $path = __DIR__ . '/../fixtures';
        $manager = $this->getManager($container, $framework = 'laravel', $path);

        $this->assertNull($manager->onWorkerStart($server));
    }

    public function testOnRequest()
    {
        $this->getServer();
        $this->getManager();

        $container = $this->getContainer($this->getServer(), $this->getConfig(true));
        $container->singleton('events', function () {
            return $this->getEvent('swoole.request', false);
        });
        $container->singleton(Websocket::class, function () {
            $websocket = m::mock(Websocket::class);
            $websocket->shouldReceive('reset')
                      ->with(true)
                      ->once();

            return $websocket;
        });
        $container->singleton(Sandbox::class, function () {
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

        $container->alias(Sandbox::class, 'swoole.sandbox');

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
        $response->shouldReceive('write')
                 ->andReturnSelf();
        $response->shouldReceive('end')
                 ->once()
                 ->andReturnSelf();

        $manager = $this->getWebsocketManager();
        $manager->setApplication($container);
        $manager->onRequest($request, $response);
    }

    public function testOnRequestException()
    {
        $server = $this->getServer();
        $container = $this->getContainer($server);
        $container->singleton('events', function () {
            return $this->getEvent('swoole.request', false);
        });
        $container->singleton(Sandbox::class, function () {
            $sandbox = m::mock(Sandbox::class);
            $sandbox->shouldReceive('disable')
                    ->once();

            return $sandbox;
        });

        $container->alias(Sandbox::class, 'swoole.sandbox');

        $container->singleton(ExceptionHandler::class, function () {
            $handler = m::mock(ExceptionHandler::class);
            $handler->shouldReceive('render')
                    ->once();

            return $handler;
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
        $response->shouldReceive('write')
                 ->andReturnSelf();
        $response->shouldReceive('end')
                 ->once()
                 ->andReturnSelf();

        $manager = $this->getManager($container);
        $manager->setApplication($container);
        $manager->onRequest($request, $response);
    }

    public function testOnTask()
    {
        $container = $this->getContainer();
        $container->singleton('events', function () {
            return $this->getEvent('swoole.task');
        });
        $manager = $this->getManager($container);
        $manager->onTask('server', 'taskId', 'workerId', 'data');
    }

    public function testOnShutdown()
    {
        $pidManager = m::mock(PidManager::class);
        $pidManager->shouldReceive('delete')->once();

        $container = $this->getContainer();
        $container->singleton(PidManager::class, function () use ($pidManager) {
            return $pidManager;
        });

        $manager = $this->getManager($container);
        $manager->onShutdown();
    }

    public function testSetParser()
    {
        $parser = m::mock(Parser::class);
        $manager = $this->getManager();
        $manager->setPayloadParser($parser);

        $this->assertSame($parser, $manager->getPayloadParser());
    }

    public function testSetWebsocketHandler()
    {
        $handler = m::mock(HandlerContract::class);
        $manager = $this->getManager();
        $manager->setWebsocketHandler($handler);

        $this->assertSame($handler, $manager->getWebsocketHandler());
    }

    public function testLogServerError()
    {
        $exception = new \Exception;
        $container = $this->getContainer();
        $container->singleton(ExceptionHandler::class, function () use ($exception) {
            $handler = m::mock(ExceptionHandler::class);
            $handler->shouldReceive('report')
                    ->with($exception)
                    ->once();

            return $handler;
        });
        $manager = $this->getManager($container);
        $manager->setApplication($container);
        $manager->logServerError($exception);
    }

    public function testOnOpen()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('rawcontent')
                ->once()
                ->andReturn([]);
        $request->fd = 1;

        $container = $this->getContainer();
        $container->singleton(Websocket::class, function () {
            $websocket = m::mock(Websocket::class);
            $websocket->shouldReceive('reset')
                      ->with(true)
                      ->once()
                      ->andReturnSelf();
            $websocket->shouldReceive('setSender')
                      ->with(1)
                      ->once();
            $websocket->shouldReceive('eventExists')
                      ->with('connect')
                      ->once()
                      ->andReturn(true);
            $websocket->shouldReceive('setContainer')
                      ->with(m::type(Container::class))
                      ->once();
            $websocket->shouldReceive('call')
                      ->with('connect', m::type('Illuminate\Http\Request'))
                      ->once();

            return $websocket;
        });
        $container->singleton(Sandbox::class, function () {
            $sandbox = m::mock(Sandbox::class);
            $sandbox->shouldReceive('setRequest')
                    ->with(m::type('Illuminate\Http\Request'))
                    ->once();
            $sandbox->shouldReceive('enable')
                    ->once();
            $sandbox->shouldReceive('disable')
                    ->once();
            $sandbox->shouldReceive('getApplication')
                    ->once()
                    ->andReturn(m::mock(Container::class));

            return $sandbox;
        });

        $container->alias(Sandbox::class, 'swoole.sandbox');

        $handler = m::mock(HandlerContract::class);
        $handler->shouldReceive('onOpen')
                ->with(1, m::type('Illuminate\Http\Request'))
                ->andReturn(true);

        $manager = $this->getWebsocketManager();
        $manager->setApplication($container);
        $manager->setWebsocketHandler($handler);
        $manager->onOpen(m::mock('server'), $request);
    }

    public function testOnHandShake()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('rawcontent')
                ->once()
                ->andReturn([]);
        $request->fd = 1;
        $request->header['sec-websocket-key'] = 'Bet8DkPFq9ZxvIBvPcNy1A==';

        $response = m::mock(Response::class);
        $response->shouldReceive('header')->withAnyArgs()->times(4)->andReturnSelf();
        $response->shouldReceive('status')->with(101)->once()->andReturnSelf();
        $response->shouldReceive('end')->withAnyArgs()->once()->andReturnSelf();

        $container = $this->getContainer($this->getServer(), $this->getConfig(true));
        $container->singleton(Websocket::class, function () {
            $websocket = m::mock(Websocket::class);
            $websocket->shouldReceive('reset')
                      ->with(true)
                      ->once()
                      ->andReturnSelf();
            $websocket->shouldReceive('setSender')
                      ->with(1)
                      ->once();
            $websocket->shouldReceive('eventExists')
                      ->with('connect')
                      ->once()
                      ->andReturn(true);
            $websocket->shouldReceive('setContainer')
                      ->with(m::type(Container::class))
                      ->once();
            $websocket->shouldReceive('call')
                      ->with('connect', m::type('Illuminate\Http\Request'))
                      ->once();

            return $websocket;
        });
        $container->singleton(Sandbox::class, function () {
            $sandbox = m::mock(Sandbox::class);
            $sandbox->shouldReceive('setRequest')
                    ->with(m::type('Illuminate\Http\Request'))
                    ->once();
            $sandbox->shouldReceive('enable')
                    ->once();
            $sandbox->shouldReceive('disable')
                    ->once();
            $sandbox->shouldReceive('getApplication')
                    ->once()
                    ->andReturn(m::mock(Container::class));

            return $sandbox;
        });

        $container->alias(Sandbox::class, 'swoole.sandbox');

        $handler = m::mock(HandlerContract::class);
        $handler->shouldReceive('onOpen')
                ->with(1, m::type('Illuminate\Http\Request'))
                ->andReturn(true);

        $manager = $this->getWebsocketManager();
        $manager->setApplication($container);
        $manager->setWebsocketHandler($handler);
        $manager->onHandShake($request, $response);
    }

    public function testOnMessage()
    {
        $frame = m::mock('frame');
        $frame->fd = 1;

        $parser = m::mock(Parser::class);
        $parser->shouldReceive('execute')
               ->with('server', $frame)
               ->once()
               ->andReturn(false);
        $parser->shouldReceive('decode')
               ->with($frame)
               ->once()
               ->andReturn($payload = [
                   'event' => 'event',
                   'data' => 'data',
               ]);

        $container = $this->getContainer();
        $container->singleton(Websocket::class, function () use ($payload) {
            $websocket = m::mock(Websocket::class);
            $websocket->shouldReceive('reset')
                      ->with(true)
                      ->once()
                      ->andReturnSelf();
            $websocket->shouldReceive('setSender')
                      ->with(1)
                      ->once();
            $websocket->shouldReceive('eventExists')
                      ->with($payload['event'])
                      ->once()
                      ->andReturn(true);
            $websocket->shouldReceive('call')
                      ->with($payload['event'], $payload['data'])
                      ->once();

            return $websocket;
        });
        $container->singleton(Sandbox::class, function () {
            $sandbox = m::mock(Sandbox::class);
            $sandbox->shouldReceive('enable')
                    ->once();
            $sandbox->shouldReceive('disable')
                    ->once();

            return $sandbox;
        });

        $container->alias(Sandbox::class, 'swoole.sandbox');

        $manager = $this->getWebsocketManager();
        $manager->setApplication($container);
        $manager->setPayloadParser($parser);
        $manager->onMessage('server', $frame);
    }

    public function testOnClose()
    {
        $fd = 1;
        $app = $this->getContainer();
        $app->singleton(Websocket::class, function () use ($fd) {
            $websocket = m::mock(Websocket::class);
            $websocket->shouldReceive('reset')
                      ->with(true)
                      ->once()
                      ->andReturnSelf();
            $websocket->shouldReceive('setSender')
                      ->with($fd)
                      ->once();
            $websocket->shouldReceive('eventExists')
                      ->with('disconnect')
                      ->once()
                      ->andReturn(true);
            $websocket->shouldReceive('call')
                      ->with('disconnect')
                      ->once();
            $websocket->shouldReceive('leave')
                      ->once();

            return $websocket;
        });

        $server = m::mock('server');
        $server->shouldReceive('on');

        $container = $this->getContainer($server);
        $container->singleton(Server::class, function () use ($fd) {
            $server = m::mock('server');
            $server->shouldReceive('on');
            $server->taskworker = false;
            $server->master_pid = -1;
            $server->shouldReceive('connection_info')
                   ->with($fd)
                   ->once()
                   ->andReturn([
                       'websocket_status' => true,
                   ]);

            return $server;
        });

        $container->alias(Server::class, 'swoole.server');

        $manager = $this->getWebsocketManager($container);
        $manager->setApplication($app);
        $manager->onClose('server', $fd, 'reactorId');
    }

    public function testIsWebsocketPushPayload()
    {
        $payload = [
            'action' => 'push',
            'data' => [
                'opcode' => 'opcode',
                'sender' => 'sender',
                'fds' => 'fds',
                'broadcast' => 'broadcast',
                'assigned' => 'assigned',
                'event' => 'event',
                'message' => 'message',
            ]
        ];

        $manager = $this->getWebsocketManager();

        $this->assertTrue($manager->isWebsocketPushPayload($payload));
        $this->assertFalse($manager->isWebsocketPushPayload(['foo' => 'bar']));
    }

    public function testPushMessage()
    {
        $data = [
            'sender' => 1,
            'fds' => [1, 2, 3],
            'event' => $event = 'event',
            'message' => $message = 'message',
            'broadcast' => true
        ];

        $parser = m::mock(Parser::class);
        $parser->shouldReceive('encode')
               ->with($data['event'], $data['message'])
               ->once()
               ->andReturn(false);

        $server = m::mock('server');
        $server->shouldReceive('isEstablished')
            ->andReturn(true);
        $server->shouldReceive('push')
               ->twice();

        $manager = $this->getWebsocketManager();
        $manager->setPayloadParser($parser);
        $manager->pushMessage($server, $data);
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

        $container->singleton(ConfigContract::class, function () use ($config) {
            return $config;
        });
        $container->alias(ConfigContract::class, 'config');

        $container->singleton(Server::class, function () use ($server) {
            return $server;
        });
        $container->alias(Server::class, 'swoole.server');
        $container->singleton(ExceptionHandler::class, Handler::class);

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
        $config = m::mock(ConfigContract::class);
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
                  ->shouldReceive('dispatch')
                  ->with($name, m::any())
                  ->once();

        $event = $default ? $event->with($name, m::any()) : $event->with($name);

        return $event->getMock();
    }

    protected function mockMethod($name, \Closure $function, $namespace = null)
    {
        parent::mockMethod($name, $function, 'SwooleTW\Http\Server');
    }
}

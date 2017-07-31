<?php

/*
 * This file is part of the huang-yi/laravel-swoole-http package.
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Http;

use HuangYi\Http\Server\Application;
use HuangYi\Http\Server\Request;
use HuangYi\Http\Server\Response;
use Illuminate\Contracts\Container\Container;
use Swoole\Http\Server;

class Manager
{
    /**
     * @var \Swoole\Http\Server
     */
    protected $server;

    /**
     * Container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var \HuangYi\Http\Server\Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $framework;

    /**
     * Server events.
     *
     * @var array
     */
    protected $events = [
        'start', 'shutDown', 'workerStart', 'workerStop', 'timer', 'packet',
        'close', 'bufferFull', 'bufferEmpty', 'task', 'finish', 'pipeMessage',
        'workerError', 'managerStart', 'managerStop', 'request',
    ];

    /**
     * HTTP server manager constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param string $framework
     */
    public function __construct(Container $container, $framework)
    {
        $this->container = $container;
        $this->framework = $framework;

        $this->initialize();
    }

    /**
     * Run swoole_http_server.
     */
    public function run()
    {
        $this->server->start();
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->setProcessName('manager process');

        $this->createSwooleHttpServer();
        $this->configureSwooleHttpServer();
        $this->setSwooleHttpServerListeners();
    }

    /**
     * Creates swoole_http_server.
     */
    protected function createSwooleHttpServer()
    {
        $host = $this->container['config']->get('http.server.host');
        $port = $this->container['config']->get('http.server.port');

        $this->server = new Server($host, $port);
    }

    /**
     * Sets swoole_http_server configurations.
     */
    protected function configureSwooleHttpServer()
    {
        $config = $this->container['config']->get('http.server.options');

        $this->server->set($config);
    }

    /**
     * Sets swoole_http_server listeners.
     */
    protected function setSwooleHttpServerListeners()
    {
        foreach ($this->events as $event) {
            $listener = 'on' . ucfirst($event);

            if (method_exists($this, $listener)) {
                $this->server->on($event, [$this, $listener]);
            } else {
                $this->server->on($event, function () use ($event) {
                    $event = sprintf('http.%s', $event);

                    $this->container['events']->fire($event, func_get_args());
                });
            }
        }
    }

    /**
     * "onStart" listener.
     */
    public function onStart()
    {
        $this->setProcessName('master process');
        $this->createPidFile();

        $this->container['events']->fire('http.start', func_get_args());
    }

    /**
     * "onWorkerStart" listener.
     */
    public function onWorkerStart()
    {
        $this->clearCache();
        $this->setProcessName('worker process');

        $this->container['events']->fire('http.workerStart', func_get_args());

        $this->createApplication();
    }

    /**
     * "onRequest" listener.
     *
     * @param \Swoole\Http\Request $swooleRequest
     * @param \Swoole\Http\Response $swooleResponse
     */
    public function onRequest($swooleRequest, $swooleResponse)
    {
        $illuminateRequest = Request::make($swooleRequest)->toIlluminate();
        $illuminateResponse = $this->getApplication()->run($illuminateRequest);

        Response::make($illuminateResponse, $swooleResponse)->send();

        $this->getApplication()->terminate($illuminateRequest, $illuminateResponse);

        // Unset request and response.
        $swooleRequest = null;
        $swooleResponse = null;
        $illuminateRequest = null;
        $illuminateResponse = null;
    }

    /**
     * Set onShutdown listener.
     */
    public function onShutdown()
    {
        $this->removePidFile();

        $this->container['events']->fire('http.showdown', func_get_args());
    }

    /**
     * Create application.
     */
    protected function createApplication()
    {
        return $this->application = Application::make($this->framework);
    }

    /**
     * Get application.
     *
     * @return \HuangYi\Http\Server\Application
     */
    protected function getApplication()
    {
        if (! $this->application instanceof Application) {
            $this->createApplication();
        }

        return $this->application;
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    protected function getPidFile()
    {
        return $this->container['config']->get('http.server.options.pid_file');
    }

    /**
     * Create pid file.
     */
    protected function createPidFile()
    {
        $pidFile = $this->getPidFile();
        $pid = $this->server->master_pid;

        file_put_contents($pidFile, $pid);
    }

    /**
     * Remove pid file.
     */
    protected function removePidFile()
    {
        unlink($this->getPidFile());
    }

    /**
     * Clear APC or OPCache.
     */
    protected function clearCache()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Sets process name.
     *
     * @param $process
     */
    protected function setProcessName($process)
    {
        $serverName = 'swoole_http_server';
        $appName = $this->container['config']->get('app.name', 'Laravel');

        $name = sprintf('%s: %s for %s', $serverName, $process, $appName);

        swoole_set_process_name($name);
    }
}

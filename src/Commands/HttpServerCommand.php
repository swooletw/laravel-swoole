<?php

namespace SwooleTW\Http\Commands;

use Throwable;
use Swoole\Process;
use Illuminate\Support\Arr;
use SwooleTW\Http\Helpers\OS;
use Illuminate\Console\Command;
use SwooleTW\Http\Server\Manager;
use Illuminate\Console\OutputStyle;
use SwooleTW\Http\HotReload\FSEvent;
use SwooleTW\Http\HotReload\FSOutput;
use SwooleTW\Http\HotReload\FSProcess;
use SwooleTW\Http\Server\AccessOutput;
use SwooleTW\Http\Server\PidManager;
use SwooleTW\Http\Middleware\AccessLog;
use SwooleTW\Http\Server\Facades\Server;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @codeCoverageIgnore
 */
class HttpServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:http {action : start|stop|restart|reload|infos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swoole HTTP Server controller.';

    /**
     * The console command action. start|stop|restart|reload
     *
     * @var string
     */
    protected $action;

    /**
     *
     * The pid.
     *
     * @var int
     */
    protected $currentPid;

    /**
     * The configs for this package.
     *
     * @var array
     */
    protected $config;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->checkEnvironment();
        $this->loadConfigs();
        $this->initAction();
        $this->hookAction();
        $this->runAction();
    }

    /**
     * Load configs.
     */
    protected function loadConfigs()
    {
        $this->config = $this->laravel->make('config')->get('swoole_http');
    }
    
    /**
     * Hook action
     */
    protected function hookAction()
    {
        // custom hook task before starting server
    }

    /**
     * Run action.
     */
    protected function runAction()
    {
        $this->{$this->action}();
    }

    /**
     * Run swoole_http_server.
     */
    protected function start()
    {
        if ($this->isRunning()) {
            $this->error('Failed! swoole_http_server process is already running.');

            return;
        }

        $host = Arr::get($this->config, 'server.host');
        $port = Arr::get($this->config, 'server.port');
        $hotReloadEnabled = Arr::get($this->config, 'hot_reload.enabled');
        $accessLogEnabled = Arr::get($this->config, 'server.access_log');

        $this->info('Starting swoole http server...');
        $this->info("Swoole http server started: <http://{$host}:{$port}>");
        if ($this->isDaemon()) {
            $this->info(
                '> (You can run this command to ensure the ' .
                'swoole_http_server process is running: ps aux|grep "swoole")'
            );
        }

        $manager = $this->laravel->make(Manager::class);
        $server = $this->laravel->make(Server::class);

        if ($accessLogEnabled) {
            $this->registerAccessLog();
        }

        if ($hotReloadEnabled) {
            $manager->addProcess($this->getHotReloadProcess($server));
        }

        $manager->run();
    }

    /**
     * Stop swoole_http_server.
     */
    protected function stop()
    {
        if (! $this->isRunning()) {
            $this->error("Failed! There is no swoole_http_server process running.");

            return;
        }

        $this->info('Stopping swoole http server...');

        $isRunning = $this->killProcess(SIGTERM, 15);

        if ($isRunning) {
            $this->error('Unable to stop the swoole_http_server process.');

            return;
        }

        // I don't known why Swoole didn't trigger "onShutdown" after sending SIGTERM.
        // So we should manually remove the pid file.
        $this->laravel->make(PidManager::class)->delete();

        $this->info('> success');
    }

    /**
     * Restart swoole http server.
     */
    protected function restart()
    {
        if ($this->isRunning()) {
            $this->stop();
        }

        $this->start();
    }

    /**
     * Reload.
     */
    protected function reload()
    {
        if (! $this->isRunning()) {
            $this->error("Failed! There is no swoole_http_server process running.");

            return;
        }

        $this->info('Reloading swoole_http_server...');

        if (! $this->killProcess(SIGUSR1)) {
            $this->error('> failure');

            return;
        }

        $this->info('> success');
    }

    /**
     * Display PHP and Swoole misc info.
     */
    protected function infos()
    {
        $this->showInfos();
    }

    /**
     * Display PHP and Swoole miscs infos.
     */
    protected function showInfos()
    {
        $isRunning = $this->isRunning();
        $host = Arr::get($this->config, 'server.host');
        $port = Arr::get($this->config, 'server.port');
        $reactorNum = Arr::get($this->config, 'server.options.reactor_num');
        $workerNum = Arr::get($this->config, 'server.options.worker_num');
        $taskWorkerNum = Arr::get($this->config, 'server.options.task_worker_num');
        $isWebsocket = Arr::get($this->config, 'websocket.enabled');
        $hasTaskWorker = $isWebsocket || Arr::get($this->config, 'queue.default') === 'swoole';
        $logFile = Arr::get($this->config, 'server.options.log_file');
        $pids = $this->laravel->make(PidManager::class)->read();
        $masterPid = $pids['masterPid'] ?? null;
        $managerPid = $pids['managerPid'] ?? null;

        $table = [
            ['PHP Version', 'Version' => phpversion()],
            ['Swoole Version', 'Version' => swoole_version()],
            ['Laravel Version', $this->getApplication()->getVersion()],
            ['Listen IP', $host],
            ['Listen Port', $port],
            ['Server Status', $isRunning ? 'Online' : 'Offline'],
            ['Reactor Num', $reactorNum],
            ['Worker Num', $workerNum],
            ['Task Worker Num', $hasTaskWorker ? $taskWorkerNum : 0],
            ['Websocket Mode', $isWebsocket ? 'On' : 'Off'],
            ['Master PID', $isRunning ? $masterPid : 'None'],
            ['Manager PID', $isRunning && $managerPid ? $managerPid : 'None'],
            ['Log Path', $logFile],
        ];

        $this->table(['Name', 'Value'], $table);
    }

    /**
     * Initialize command action.
     */
    protected function initAction()
    {
        $this->action = $this->argument('action');

        if (! in_array($this->action, ['start', 'stop', 'restart', 'reload', 'infos'], true)) {
            $this->error(
                "Invalid argument '{$this->action}'. Expected 'start', 'stop', 'restart', 'reload' or 'infos'."
            );

            return;
        }
    }

    /**
     * @param \SwooleTW\Http\Server\Facades\Server $server
     *
     * @return \Swoole\Process
     */
    protected function getHotReloadProcess($server)
    {
        $recursively = Arr::get($this->config, 'hot_reload.recursively');
        $directory = Arr::get($this->config, 'hot_reload.directory');
        $filter = Arr::get($this->config, 'hot_reload.filter');
        $log = Arr::get($this->config, 'hot_reload.log');

        $cb = function (FSEvent $event) use ($server, $log) {
            $log ? $this->info(FSOutput::format($event)) : null;
            $server->reload();
        };

        return (new FSProcess($filter, $recursively, $directory))->make($cb);
    }

    /**
     * If Swoole process is running.
     *
     * @param int $pid
     *
     * @return bool
     */
    public function isRunning()
    {
        $pids = $this->laravel->make(PidManager::class)->read();

        if (! count($pids)) {
            return false;
        }

        $masterPid = $pids['masterPid'] ?? null;
        $managerPid = $pids['managerPid'] ?? null;

        if ($managerPid) {
            // Swoole process mode
            return $masterPid && $managerPid && Process::kill((int) $managerPid, 0);
        }

        // Swoole base mode, no manager process
        return $masterPid && Process::kill((int) $masterPid, 0);
    }

    /**
     * Kill process.
     *
     * @param int $sig
     * @param int $wait
     *
     * @return bool
     */
    protected function killProcess($sig, $wait = 0)
    {
        Process::kill(
            Arr::first($this->laravel->make(PidManager::class)->read()),
            $sig
        );

        if ($wait) {
            $start = time();

            do {
                if (! $this->isRunning()) {
                    break;
                }

                usleep(100000);
            } while (time() < $start + $wait);
        }

        return $this->isRunning();
    }

    /**
     * Return daemonize config.
     */
    protected function isDaemon(): bool
    {
        return Arr::get($this->config, 'server.options.daemonize', false);
    }

    /**
     * Check running enironment.
     */
    protected function checkEnvironment()
    {
        if (OS::is(OS::WIN)) {
            $this->error('Swoole extension doesn\'t support Windows OS.');

            exit(1);
        }

        if (! extension_loaded('swoole')) {
            $this->error('Can\'t detect Swoole extension installed.');

            exit(1);
        }

        if (! version_compare(swoole_version(), '4.3.1', 'ge')) {
            $this->error('Your Swoole version must be higher than `4.3.1`.');

            exit(1);
        }
    }

    /**
     * Register access log services.
     */
    protected function registerAccessLog()
    {
        $this->laravel->singleton(OutputStyle::class, function () {
            return new OutputStyle($this->input, $this->output);
        });

        $this->laravel->singleton(AccessOutput::class, function () {
            return new AccessOutput(new ConsoleOutput);
        });

        $this->laravel->singleton(AccessLog::class, function (Container $container) {
            return new AccessLog($container->make(AccessOutput::class));
        });
    }
}

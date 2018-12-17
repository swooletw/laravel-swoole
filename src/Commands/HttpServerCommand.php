<?php

namespace SwooleTW\Http\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Swoole\Process;
use Throwable;

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
    protected $pid;

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
        if ($this->isRunning($this->getPid())) {
            $this->error('Failed! swoole_http_server process is already running.');

            return;
        }

        $host = $this->config['server']['host'];
        $port = $this->config['server']['port'];

        $this->info('Starting swoole http server...');
        $this->info("Swoole http server started: <http://{$host}:{$port}>");
        if ($this->isDaemon()) {
            $this->info('> (You can run this command to ensure the ' .
                'swoole_http_server process is running: ps aux|grep "swoole")');
        }

        $this->laravel->make('swoole.manager')->run();
    }

    /**
     * Stop swoole_http_server.
     */
    protected function stop()
    {
        $pid = $this->getPid();

        if (!$this->isRunning($pid)) {
            $this->error("Failed! There is no swoole_http_server process running.");

            return;
        }

        $this->info('Stopping swoole http server...');

        $isRunning = $this->killProcess($pid, SIGTERM, 15);

        if ($isRunning) {
            $this->error('Unable to stop the swoole_http_server process.');

            return;
        }

        // I don't known why Swoole didn't trigger "onShutdown" after sending SIGTERM.
        // So we should manually remove the pid file.
        $this->removePidFile();

        $this->info('> success');
    }

    /**
     * Restart swoole http server.
     */
    protected function restart()
    {
        $pid = $this->getPid();

        if ($this->isRunning($pid)) {
            $this->stop();
        }

        $this->start();
    }

    /**
     * Reload.
     */
    protected function reload()
    {
        $pid = $this->getPid();

        if (!$this->isRunning($pid)) {
            $this->error("Failed! There is no swoole_http_server process running.");

            return;
        }

        $this->info('Reloading swoole_http_server...');

        $isRunning = $this->killProcess($pid, SIGUSR1);

        if (!$isRunning) {
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
        $pid = $this->getPid();
        $isRunning = $this->isRunning($pid);
        $host = $this->config['server']['host'];
        $port = $this->config['server']['port'];
        $reactorNum = $this->config['server']['options']['reactor_num'];
        $workerNum = $this->config['server']['options']['worker_num'];
        $taskWorkerNum = $this->config['server']['options']['task_worker_num'];
        $isWebsocket = $this->config['websocket']['enabled'];
        $logFile = $this->config['server']['options']['log_file'];

        $table = [
            ['PHP Version', 'Version' => phpversion()],
            ['Swoole Version', 'Version' => swoole_version()],
            ['Laravel Version', $this->getApplication()->getVersion()],
            ['Listen IP', $host],
            ['Listen Port', $port],
            ['Server Status', $isRunning ? 'Online' : 'Offline'],
            ['Reactor Num', $reactorNum],
            ['Worker Num', $workerNum],
            ['Task Worker Num', $isWebsocket ? $taskWorkerNum : 0],
            ['Websocket Mode', $isWebsocket ? 'On' : 'Off'],
            ['PID', $isRunning ? $pid : 'None'],
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

        if (!in_array($this->action, ['start', 'stop', 'restart', 'reload', 'infos'], true)) {
            $this->error("Invalid argument '{$this->action}'. Expected 'start', 'stop', 'restart', 'reload' or 'infos'.");

            return;
        }
    }

    /**
     * If Swoole process is running.
     *
     * @param int $pid
     *
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (!$pid) {
            return false;
        }

        try {
            return Process::kill($pid, 0);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Kill process.
     *
     * @param int $pid
     * @param int $sig
     * @param int $wait
     *
     * @return bool
     */
    protected function killProcess($pid, $sig, $wait = 0)
    {
        Process::kill($pid, $sig);

        if ($wait) {
            $start = time();

            do {
                if (!$this->isRunning($pid)) {
                    break;
                }

                usleep(100000);
            } while (time() < $start + $wait);
        }

        return $this->isRunning($pid);
    }

    /**
     * Get pid.
     *
     * @return int|null
     */
    protected function getPid()
    {
        if ($this->pid) {
            return $this->pid;
        }

        $path = $this->getPidPath();

        return $this->pid = file_exists($path)
            ? (int)file_get_contents($path) ?? $this->removePidFile()
            : null;
    }

    /**
     * Get Pid file path.
     *
     * @return string
     */
    protected function getPidPath()
    {
        return $this->config['server']['options']['pid_file'];
    }

    /**
     * Remove Pid file.
     */
    protected function removePidFile()
    {
        if (file_exists($this->getPidPath())) {
            unlink($this->getPidPath());
        }
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
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->error("Swoole extension doesn't support Windows OS yet.");

            return;
        } else if (!extension_loaded('swoole')) {
            $this->error("Can't detect Swoole extension installed.");

            return;
        } else if (!version_compare(swoole_version(), '4.0.0', 'ge')) {
            $this->error("Your Swoole version must be higher than 4.0 to use coroutine.");

            return;
        }
    }
}

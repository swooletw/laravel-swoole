<?php

namespace SwooleTW\Http\Task\Connectors;


use Illuminate\Foundation\Application;
use Illuminate\Queue\Connectors\ConnectorInterface;
use SwooleTW\Http\Task\V56\SwooleTaskQueue as STQ_V56;
use SwooleTW\Http\Task\V57\SwooleTaskQueue as STQ_V57;

/**
 * Class SwooleTaskConnector
 */
class SwooleTaskConnector implements ConnectorInterface
{
    /**
     * Swoole Server Instance
     *
     * @var \Swoole\Http\Server
     */
    protected $swoole;

    /**
     * Create a new Swoole Async task connector instance.
     *
     * @param  \Swoole\Http\Server $swoole
     *
     * @return void
     */
    public function __construct($swoole)
    {
        $this->swoole = $swoole;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array $config
     *
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $isGreater = version_compare(Application::VERSION, '5.7', '>=');

        return ($isGreater) ? new STQ_V57($this->swoole) : new STQ_V56($this->swoole);
    }
}

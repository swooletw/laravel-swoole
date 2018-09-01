<?php

namespace SwooleTW\Http\AsyncTask\Connectors;

use SwooleTW\Http\AsyncTask\AsyncTaskQueue;
use Illuminate\Queue\Connectors\ConnectorInterface;

class AsyncTaskConnector implements ConnectorInterface
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
     * @return void
     */
    public function __construct($swoole)
    {
        $this->swoole = $swoole;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new AsyncTaskQueue($this->swoole);
    }
}

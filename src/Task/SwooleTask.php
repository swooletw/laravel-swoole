<?php

namespace SwooleTW\Http\Task;

use Closure;
use SuperClosure\Serializer;

class SwooleTask
{
    /**
     * The swoole http server instace.
     *
     * @var \Swoole\Http\Server
     */
    protected $server;

    /**
     * Registered swoole tasks.
     *
     * @var array
     */
    protected $tasks = [];

    /**
     * Task constructor
     */
    public function __construct($server)
    {
        $this->server = $server->getServer();
    }

    /**
     * Swoole Async task
     *
     * @param object  $task
     * @param mixed  $callback
     * @param int  $taskWorkerId
     *
     * @return mixed
     */
    public function async(callable $task, callable $callback = null, $taskWorkerId = 1)
    {
        $serializedTask = $task instanceof Closure ? (new Serializer)->serialize($task) : serialize($task);

        return $this->server->task($serializedTask, $taskWorkerId, $callback);
    }
}

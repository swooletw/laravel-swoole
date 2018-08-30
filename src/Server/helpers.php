<?php

use SwooleTW\Http\Task\Facades\SwooleTask;

/**
 * This is only for `function not exists` in config/swoole_http.php.
 */
if (! function_exists('swoole_cpu_num')) {
    function swoole_cpu_num()
    {
        return;
    }
}


if (! function_exists('swoole_async_task')) {
    /**
     * Swoole Async task helper.
     *
     * @param object  $task
     * @param mixed  $callback
     * @param int  $taskWorkerId
     *
     * @return mixed
     */
    function swoole_async_task($task, $callback = null, $taskWorkerId = 1)
    {
        return SwooleTask::async($task, $callback, $taskWorkerId);
    }
}

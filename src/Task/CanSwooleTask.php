<?php

namespace SwooleTW\Http\Task;

trait CanSwooleTask
{
    /**
     * Bind swoole task to Laravel app container.
     */
    protected function bindSwooleTask()
    {
        $this->app->singleton('swoole.task', function () {
            return new SwooleTask($this);
        });
    }
}

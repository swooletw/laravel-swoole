<?php

namespace SwooleTW\Http\Task;

use Illuminate\Queue\Jobs\Job;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class SwooleTaskJob extends Job implements JobContract
{
    /**
     * The Swoole Server instance.
     *
     * @var \Swoole\Http\Server
     */
    protected $swoole;

    /**
     * The Swoole async job raw payload.
     *
     * @var array
     */
    protected $job;

    /**
     * The Task id
     *
     * @var int
     */
    protected $taskId;

    /**
     * The src worker Id
     *
     * @var int
     */
    protected $srcWrokerId;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Swoole\Http\Server  $swoole
     * @param  string  $job
     *
     * @return void
     */
    public function __construct(Container $container, $swoole, $job, $taskId, $srcWrokerId)
    {
        $this->container = $container;
        $this->swoole = $swoole;
        $this->job = $job;
        $this->taskId = $taskId;
        $this->srcWorkderId = $srcWrokerId;
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return ($this->job['attempts'] ?? null) + 1;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job;
    }


    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->taskId;
    }
}

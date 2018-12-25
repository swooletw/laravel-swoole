<?php

namespace SwooleTW\Http\Task;

use Illuminate\Queue\Jobs\Job;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

/**
 * Class SwooleTaskJob
 */
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
     * @var int
     */
    protected $srcWorkderId;

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param  \Swoole\Http\Server $swoole
     * @param  string $job
     * @param  int $taskId
     * @param $srcWrokerId
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
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        if (method_exists($this, 'resolveAndFire')) {
            $this->resolveAndFire(json_decode($this->getRawBody(), true));
        } else {
            parent::fire();
        }
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

    /**
     * Get the service container instance.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}

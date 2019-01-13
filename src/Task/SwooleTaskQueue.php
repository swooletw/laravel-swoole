<?php

namespace SwooleTW\Http\Task;

use Exception;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Swoole\Timer;

/**
 * Class SwooleTaskQueue (5.7)
 */
class SwooleTaskQueue extends Queue implements QueueContract
{
    /**
     * Swoole Connector
     *
     * @var \Swoole\Http\Server
     */
    protected $swoole;

    /**
     * Create Async Task instance.
     *
     * @param \Swoole\Http\Server $swoole
     */
    public function __construct($swoole)
    {
        $this->swoole = $swoole;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->swoole->task($payload, ! is_numeric($queue) ? -1 : (int)$queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int $delay
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return Timer::after($this->secondsUntil($delay) * 1000, function () use ($job, $data, $queue) {
            return $this->push($job, $data, $queue);
        });
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * @param  string $job
     * @param  string $queue
     * @param  mixed $data
     *
     * @throws \Exception
     */
    protected function createStringPayload($job, $queue, $data)
    {
        throw new Exception('Unsupported empty data');
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     *
     * @return int
     */
    public function size($queue = null)
    {
        return -1;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     *
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        return null;
    }
}

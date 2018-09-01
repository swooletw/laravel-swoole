<?php

namespace SwooleTW\Http\AsyncTask;

use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class AsyncTaskQueue extends Queue implements QueueContract
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
     * @param \Swoole\Http\Server  $swoole
     */
    public function __construct($swoole)
    {
        $this->swoole = $swoole;
    }


    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->swoole->task($payload, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return swoole_timer_after($this->secondsUntil($delay), function () use ($job, $data, $queue) {
            return $this->push($job, $data, $queue);
        });
    }

     /**
     * Create a payload for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return array
     */
    protected function createObjectPayload($job)
    {
        return [
            'maxTries' => $job->tries ?? null,
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'timeout' => $job->timeout ?? null,
            'timeoutAt' => $this->getJobExpiration($job),
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job)
            ]
        ];
    }

     /**
     * Create a typical, string based queue payload array.
     *
     * @param  string  $job
     * @param  mixed  $data
     *
     * @throws Expcetion
     */
    protected function createStringPayload($job, $data)
    {
        throw new Exception("Unsupported empty data");
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return -1;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        return null;
    }
}

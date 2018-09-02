<?php

namespace SwooleTW\Http\Tests\Task;

use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Task\SwooleTaskQueue;

class SwooleQueueTest extends TestCase
{
    public function testPushProperlyPushesJobOntoSwoole()
    {
        $server = $this->getServer();

        $queue = new SwooleTaskQueue($server);
        $server->shouldReceive('task')->once();
        $queue->push(new FakeJob);
    }

    protected function getServer()
    {
        $server = m::mock('server');
        $server->shouldReceive('on');
        $server->taskworker = false;
        $server->master_pid = -1;

        return $server;
    }
}



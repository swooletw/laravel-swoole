<?php

namespace SwooleTW\Http\Tests\Task;

use Illuminate\Foundation\Application;
use Mockery as m;
use SwooleTW\Http\Task\V57\SwooleTaskQueue as STQ_V57;
use SwooleTW\Http\Task\V56\SwooleTaskQueue as STQ_V56;
use SwooleTW\Http\Tests\TestCase;

class SwooleQueueTest extends TestCase
{
    public function testPushProperlyPushesJobOntoSwoole()
    {
        $version = Application::VERSION;
        $isGreater = version_compare($version, '5.7', '>=');
        $taskClass = $isGreater ? STQ_V57::class : STQ_V56::class;

        $server = $this->getServer();
        /** @var \Illuminate\Contracts\Queue\Queue $queue */
        $queue = new $taskClass($server);
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



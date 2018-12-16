<?php

namespace SwooleTW\Http\Tests\Task;


use Illuminate\Foundation\Application;
use Mockery as m;
use SwooleTW\Http\Task\QueueFactory;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Task\V56\SwooleTaskQueue as STQ_V56;
use SwooleTW\Http\Task\V57\SwooleTaskQueue as STQ_V57;

class SwooleQueueTest extends TestCase
{
    public function testPushProperlyPushesJobOntoSwoole()
    {
        $server = $this->getServer();
        $queue = QueueFactory::make($server, Application::VERSION);
        $server->shouldReceive('task')->once();
        $queue->push(new FakeJob);
    }

    public function testQueueFactoryVersionClass()
    {
        $server = $this->getServer();
        $queue = QueueFactory::make($server, Application::VERSION);

        $isGreater = version_compare(Application::VERSION, QueueFactory::CHANGE_VERSION, '>=');
        $expected = $isGreater ? STQ_V57::class : STQ_V56::class;

        $this->assertInstanceOf($expected, $queue);
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



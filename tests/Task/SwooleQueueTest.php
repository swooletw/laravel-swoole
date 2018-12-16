<?php

namespace SwooleTW\Http\Tests\Task;


use Illuminate\Foundation\Application;
use Mockery as m;
use SwooleTW\Http\Task\QueueFactory;
use SwooleTW\Http\Tests\TestCase;

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

        var_dump(Application::VERSION, QueueFactory::CHANGE_VERSION);
        $isGreater = version_compare(Application::VERSION, QueueFactory::CHANGE_VERSION, '>=');
        $expected = $isGreater ? 'SwooleTW\Http\Task\V57\SwooleTaskQueue' : 'SwooleTW\Http\Task\V56\SwooleTaskQueue';

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



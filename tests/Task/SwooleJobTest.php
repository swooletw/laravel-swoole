<?php

namespace SwooleTW\Http\Tests\Task;

use Illuminate\Container\Container;
use Mockery as m;
use SwooleTW\Http\Task\SwooleTaskJob;
use SwooleTW\Http\Tests\TestCase;

class SwooleJobTest extends TestCase
{
    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('stdClass'));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);
        $job->fire();
    }

    protected function getJob()
    {
        return new SwooleTaskJob(
            m::mock(Container::class),
            $this->getServer(),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            1, 1
        );
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

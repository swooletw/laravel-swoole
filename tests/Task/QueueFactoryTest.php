<?php

namespace SwooleTW\Http\Tests\Task;

use Illuminate\Support\Str;
use Mockery as m;
use SwooleTW\Http\Helpers\FW;
use SwooleTW\Http\Task\QueueFactory;
use SwooleTW\Http\Tests\TestCase;

/**
 * Class QueueFactoryTest
 *
 * TODO Temporary test - needed abstraction
 */
class QueueFactoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->mockEnv('Laravel\Lumen');
    }

    public function testItHasNeededStubByVersion()
    {
        $version = FW::version();

        $search = version_compare($version, '5.7', '>=') ? '5.7' : '5.6';
        $stub = QueueFactory::stub($version);

        $this->assertTrue(Str::contains($stub, $search));
    }

    public function testItCanCompareNeededStubByVersion()
    {
        $version = '5.6';
        $search = '5.7';

        $stub = QueueFactory::stub($version);

        $this->assertNotTrue(Str::contains($stub, $search));
    }

    public function testItCanMakeNeededQueueByVersion()
    {
        $version = FW::version();

        $server = $this->getServer();
        $queue = QueueFactory::make($server, $version);

        $this->assertInstanceOf(QueueFactory::QUEUE_CLASS, $queue);
    }

    protected function getServer()
    {
        $server = m::mock('server');
        $server->taskworker = false;
        $server->master_pid = -1;

        return $server;
    }
}
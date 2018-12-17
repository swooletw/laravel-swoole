<?php


namespace SwooleTW\Http\Tests\Task;


use Mockery as m;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
    public function testItHasNeededStubByVersion()
    {
        $version = '5.7';

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

    public function testItCanCopyNeededStubByVersion()
    {
        $version = '5.7';

        $search = version_compare($version, '5.7', '>=') ? '5.7' : '5.6';
        $stub = QueueFactory::stub($version);

        $this->assertTrue(Str::contains($stub, $search));

        QueueFactory::copy($stub, true);

        $fileVersion = null;
        $ref = new \ReflectionClass(QueueFactory::QUEUE_CLASS);
        if (preg_match(FW::VERSION_WITHOUT_BUG_FIX, $ref->getDocComment(), $result)) {
            $fileVersion = Arr::first($result);
        }

        $versionEquals = version_compare($fileVersion, $version, '>=');

        $this->assertTrue($versionEquals);
    }

    public function testItCanMakeNeededQueueByVersion()
    {
        $version = '5.7';

        $server = $this->getServer();
        $queue = QueueFactory::make($server, $version);

        $this->assertInstanceOf(QueueFactory::QUEUE_CLASS, $queue);
    }

    protected function getServer()
    {
        $server = m::mock('server');
//        $server->shouldReceive('on');
        $server->taskworker = false;
        $server->master_pid = -1;

        return $server;
    }
}
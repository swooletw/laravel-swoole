<?php

namespace SwooleTW\Http\Tests\Coroutine;

use Illuminate\Support\Str;
use SwooleTW\Http\Coroutine\Connectors\ConnectorFactory;
use SwooleTW\Http\Helpers\FW;
use SwooleTW\Http\Tests\TestCase;

/**
 * Class ConnectorFactoryTest
 *
 * TODO Temporary test - needed abstraction
 */
class ConnectorFactoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->mockEnv('Laravel\Lumen');
    }

    public function testItHasNeededStubByVersion()
    {
        $version = FW::version();

        $search = version_compare($version, '5.6', '>=') ? '5.6' : '5.5';
        $stub = ConnectorFactory::stub($version);

        $this->assertTrue(Str::contains($stub, $search));
    }

    public function testItCanCompareNeededStubByVersion()
    {
        $version = '5.5';
        $search = '5.6';

        $stub = ConnectorFactory::stub($version);

        $this->assertNotTrue(Str::contains($stub, $search));
    }

    public function testItCanMakeNeededQueueByVersion()
    {
        $version = FW::version();
        $queue = ConnectorFactory::make($version);

        $this->assertInstanceOf(ConnectorFactory::CONNECTOR_CLASS, $queue);
    }
}
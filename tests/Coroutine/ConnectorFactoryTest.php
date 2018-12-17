<?php


namespace SwooleTW\Http\Tests\Coroutine;


use Illuminate\Support\Arr;
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

    public function testItCanCopyNeededStubByVersion()
    {
        $version = '5.6';

        $search = version_compare($version, '5.6', '>=') ? '5.6' : '5.5';
        $stub = ConnectorFactory::stub($version);

        $this->assertTrue(Str::contains($stub, $search));

        ConnectorFactory::copy($stub, true);

        $fileVersion = null;
        $ref = new \ReflectionClass(ConnectorFactory::CONNECTOR_CLASS);
        if (preg_match(FW::VERSION_WITHOUT_BUG_FIX, $ref->getDocComment(), $result)) {
            $fileVersion = Arr::first($result);
        }

        $versionEquals = version_compare($fileVersion, $version, '>=');

        $this->assertTrue($versionEquals);
    }

    public function testItCanMakeNeededQueueByVersion()
    {
        $version = FW::version();
        $queue = ConnectorFactory::make($version);

        $this->assertInstanceOf(ConnectorFactory::CONNECTOR_CLASS, $queue);
    }
}
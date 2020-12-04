<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Server\PidManager;

class PidManagerTest extends TestCase
{
    public function testSetPidFile()
    {
        $pidFile = 'foo/bar';
        $pidManager = new PidManager($pidFile);

        $this->assertEquals($pidFile, $pidManager->file());

        $pidManager->setPidFile($pidFile = 'laravel-swoole');
        $this->assertEquals($pidFile, $pidManager->file());
    }

    public function testWrite()
    {
        $pidManager = new PidManager($pidFile = 'foo/bar');

        $this->mockMethod('is_writable', function () {
            return true;
        });

        $inputFilePath = '';
        $inputFileContent = '';
        $this->mockMethod('file_put_contents', function ($pidPath, $pidContent) use (&$inputFilePath, &$inputFileContent) {
            $inputFilePath = $pidPath;
            $inputFileContent = $pidContent;
        });

        $pidManager->write($masterPid = 1, $managerPid = 2);

        $this->assertEquals($pidFile, $inputFilePath);
        $this->assertEquals('1,2', $inputFileContent);
    }

    public function testWriteWithException()
    {
        $pidManager = new PidManager($pidFile = 'foo/bar');

        $this->mockMethod('is_writable', function () {
            return false;
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pid file "foo/bar" is not writable');

        $pidManager->write($masterPid = 1, $managerPid = 2);
    }

    public function testRead()
    {
        $pidManager = new PidManager($pidFile = 'foo/bar');

        $this->mockMethod('is_readable', function () {
            return true;
        });

        $this->mockMethod('file_get_contents', function () {
            return '1,2';
        });

        $this->assertEquals(
            ['masterPid' => '1', 'managerPid' => '2'],
            $pidManager->read()
        );
    }

    public function testDelete()
    {
        $pidManager = new PidManager($pidFile = 'foo/bar');

        $this->mockMethod('is_writable', function () {
            return true;
        });

        $unlinkCalled = false;
        $this->mockMethod('unlink', function () use (&$unlinkCalled) {
            return $unlinkCalled = true;
        });

        $pidManager->delete();

        $this->assertTrue($unlinkCalled);
    }

    protected function mockMethod($name, \Closure $function, $namespace = null)
    {
        parent::mockMethod($name, $function, 'SwooleTW\Http\Server');
    }
}

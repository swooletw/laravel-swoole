<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use ReflectionProperty;
use RuntimeException;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\Application;

class SandboxTest extends TestCase
{
    public function testRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        Sandbox::getApplication();
    }
}

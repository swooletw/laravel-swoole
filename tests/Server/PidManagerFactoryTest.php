<?php

namespace SwooleTW\Http\Tests\Server;

use SwooleTW\Http\Server\PidManager;
use SwooleTW\Http\Server\PidManagerFactory;
use SwooleTW\Http\Tests\TestCase;

class PidManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsAPidManager()
    {
        $factory = new PidManagerFactory();

        $this->assertInstanceOf(PidManager::class, $factory());
    }
}

<?php

namespace SwooleTW\Http\Tests\Server;

use Psr\Container\ContainerInterface;
use SwooleTW\Http\Server\PidManager;
use SwooleTW\Http\Server\PidManagerFactory;
use SwooleTW\Http\Tests\TestCase;

class PidManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsAPidManager()
    {
        $factory = new PidManagerFactory();

        $pidManager = $factory($this->prophesize(ContainerInterface::class)->reveal());

        $this->assertInstanceOf(PidManager::class, $pidManager);
    }
}

<?php

namespace SwooleTW\Http\Tests;

use Mockery as m;
use phpmock\Mock;
use phpmock\MockBuilder;
use SwooleTW\Http\Coroutine\Context;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function tearDown()
    {
        $this->addToAssertionCount(
            m::getContainer()->mockery_getExpectationCount()
        );

        Context::clear();
        Facade::clearResolvedInstances();
        parent::tearDown();
        m::close();
        Mock::disableAll();
    }

    protected function mockMethod($name, \Closure $function, $namespace = null)
    {
        $builder = new MockBuilder();
        $builder->setNamespace($namespace)
                ->setName($name)
                ->setFunction($function);

        $mock = $builder->build();
        $mock->enable();
    }
}

<?php

namespace SwooleTW\Http\Tests;

use Illuminate\Support\Facades\Facade;
use Mockery as m;
use phpmock\Mock;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use SwooleTW\Http\Coroutine\Context;

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
        $builder = new MockBuilder;
        $builder->setNamespace($namespace)
                ->setName($name)
                ->setFunction($function);

        $mock = $builder->build();
        $mock->enable();
    }

    protected function mockEnv(string $namespace, array $variables = [])
    {
        $this->mockMethod('env', function ($key, $value = null) use ($variables) {
            if (array_key_exists($key, $variables)) {
                return $variables[$key];
            }

            return null;
        }, $namespace);
    }
}

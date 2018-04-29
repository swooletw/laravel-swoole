<?php

namespace SwooleTW\Http\Tests;

use Mockery as m;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function tearDown()
    {
        $this->addToAssertionCount(
            m::getContainer()->mockery_getExpectationCount()
        );

        Facade::clearResolvedInstances();
        parent::tearDown();
    }
}

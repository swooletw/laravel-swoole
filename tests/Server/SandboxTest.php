<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use DeepCopy\DeepCopy;
use ReflectionProperty;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use DeepCopy\TypeMatcher\TypeMatcher;
use SwooleTW\Http\Server\Application;

class SandboxTest extends TestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testMake()
    // {
    //     $application = m::mock(Application::class);
    //     $sandbox = Sandbox::make($application);

    //     $this->assertTrue($sandbox instanceOf Sandbox);
    // }

    // public function testSetMatchers()
    // {
    //     $sandbox = $this->getSandbox();

    //     $whitelist = new ReflectionProperty(Sandbox::class, 'whitelist');
    //     $whitelist->setAccessible(true);

    //     $matchers = new ReflectionProperty(Sandbox::class, 'matchers');
    //     $matchers->setAccessible(true);

    //     $this->assertSame(
    //         count($whitelist->getValue($sandbox)),
    //         count($matchers->getValue($sandbox))
    //     );

    //     $this->assertTrue($matchers->getValue($sandbox)[0] instanceOf TypeMatcher);
    // }

    // public function testGetDeepCopy()
    // {
    //     $sandbox = $this->getSandbox();

    //     $this->assertTrue($sandbox->getDeepCopy() instanceOf DeepCopy);
    // }

    protected function getSandbox($application = null)
    {
        $app = m::mock(Application::class);

        if ($application instanceOf Application) {
            $app = $application;
        }

        return Sandbox::make($app);
    }
}

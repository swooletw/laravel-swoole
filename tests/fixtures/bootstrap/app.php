<?php

use Mockery as m;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Container\Container;

$kernel = new TestKernel;
$app = m::mock(Container::class);

$app->shouldReceive('make')
    ->with(Kernel::class)
    ->once()
    ->andReturn($kernel);
$app->shouldReceive('bootstrapWith')
    ->once()
    ->andReturn($kernel);
$app->shouldReceive('offsetExists')
    ->with('foo')
    ->once()
    ->andReturn(true);
$app->shouldReceive('make')
    ->with('foo')
    ->once();
$app->shouldReceive('singleton');
$app->shouldReceive('alias');

return $app;

class TestKernel
{
    public function bootstrappers()
    {
        return [];
    }
}

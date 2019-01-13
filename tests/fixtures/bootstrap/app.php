<?php

use Mockery as m;
use Illuminate\Contracts\Container\Container;

$app = m::mock(Container::class);
$app->shouldReceive('bootstrap')
    ->once();
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

<?php

require __DIR__ . '/vendor/autoload.php';

use SwooleTW\Http\Helpers\FW;

$source = version_compare(FW::version(), '5.7', '>=')
    ? __DIR__ . '/stubs/V57/SwooleTaskQueue.stub'
    : __DIR__ . '/stubs/V56/SwooleTaskQueue.stub';
$destination = __DIR__ . '/src/Task/SwooleTaskQueue.php';

copy($source, $destination);
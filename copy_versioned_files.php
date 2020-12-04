<?php

require __DIR__.'/vendor/autoload.php';

use SwooleTW\Http\Helpers\FW;
use SwooleTW\Http\Task\QueueFactory;
use SwooleTW\Http\Coroutine\Connectors\ConnectorFactory;

try {
    $framework = ucfirst(FW::current());
    $version = FW::version();
} catch (Throwable $e) {
    echo "No files were generated.\n";
    die;
}

$color = "\033[0;32m";
$noColor = "\033[0m";
$stubs = [];

/* Copy queue class */
$stub = QueueFactory::stub($version);
QueueFactory::copy($stub, true);
$stubs[] = $stub;

/* Copy connector class */
$stub = ConnectorFactory::stub($version);
ConnectorFactory::copy($stub, true);
$stubs[] = $stub;

foreach ($stubs as $stub) {
    echo "{$color}{$framework}{$noColor}: {$color}{$version}{$noColor}. Successfully copied stub: {$color}{$stub}{$noColor}\n";
}

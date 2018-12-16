<?php

require __DIR__ . '/vendor/autoload.php';

use SwooleTW\Http\Helpers\FW;
use SwooleTW\Http\Task\QueueFactory;

$version = FW::version();
$framework = ucfirst(FW::current());
$stub = QueueFactory::stub($version);
QueueFactory::copy($stub);
$color = "\033[0;32m";
$noColor = "\033[0m";

echo "{$color}{$framework}{$noColor}: {$color}{$version}{$noColor}. Successfully copied stub: {$color}{$stub}{$noColor}\n";

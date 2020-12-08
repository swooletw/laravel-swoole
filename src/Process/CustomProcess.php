<?php

namespace SwooleTW\Http\Process;

use Swoole\Process as SwooleProcess;

class CustomProcess
{
    public function make($server, $process_class)
    {
        if (!isset(class_implements($process_class)[ProcessContract::class])) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s must implement the interface %s',
                    $process_class,
                    ProcessContract::class
                )
            );
        }

        return new SwooleProcess(function ($process) use ($server, $process_class) {
            $p = new $process_class();
            $p->handle($server, $process);
        }, false, false);
    }
}

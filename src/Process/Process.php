<?php

namespace SwooleTW\Http\Process;

use Swoole\Process as SwooleProcess;

class Process
{
    public function make($server, $process_class)
    {
        if (!isset(class_implements($process_class)[ProcessContract::class])) {
            throw new \InvalidArgumentException('costom process error');
        }

        return new SwooleProcess(function ($process) use ($server, $process_class) {
            $p = new $process_class();
            $p->handle($server, $process);
        }, false, false);
    }
}

<?php

namespace SwooleTW\Http\Process;

use Swoole\Process as SwooleProcess;
use SwooleTW\Http\HotReload\FSEventParser;
use Symfony\Component\Process\Process as AppProcess;

class Process
{
    /**
     * FSProcess constructor.
     *
     * @param string $filter
     * @param bool $recursively
     * @param string $directory
     */
    public function __construct()
    {

    }

    public function make($server, $process_name)
    {
        return new SwooleProcess(function ($process) use ($server, $process_name) {
            $p = new $process_name($server);
            $p->handle($process);
        }, false, false);
    }
}

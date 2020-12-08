<?php


namespace SwooleTW\Http\Process;


use Swoole\Server;

interface ProcessContract
{
    /**
     * @param Server $serve
     * @param \Swoole\Process $process
     * @return mixed
     */
    public function handle($serve, $process);
}

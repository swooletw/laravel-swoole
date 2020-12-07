<?php


namespace SwooleTW\Http\Process;


interface ProcessContract
{
    public function handle($serve, $process);
}

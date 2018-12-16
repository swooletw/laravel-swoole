<?php

namespace SwooleTW\Http\Task;


use Illuminate\Contracts\Queue\Queue;
use SwooleTW\Http\Task\V56\SwooleTaskQueue as STQ_V56;
use SwooleTW\Http\Task\V57\SwooleTaskQueue as STQ_V57;

/**
 * Class QueueFactory
 */
class QueueFactory
{
    /**
     * Version with breaking changes
     *
     * @const string
     */
    public const CHANGE_VERSION = '5.7';

    /**
     * @param \Swoole\Http\Server $server
     * @param string $version
     *
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public static function make($server, string $version): Queue
    {
        return self::hasBreakingChanges($version)
            ? new STQ_V57($server)
            : new STQ_V56($server);
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    protected static function hasBreakingChanges(string $version): bool
    {
        return version_compare($version, self::CHANGE_VERSION, '>=');
    }
}

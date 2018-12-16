<?php

namespace SwooleTW\Http\Task;


use Illuminate\Contracts\Queue\Queue;

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
        $class = self::hasBreakingChanges($version)
            ? 'SwooleTW\Http\Task\V57\SwooleTaskQueue'
            : 'SwooleTW\Http\Task\V56\SwooleTaskQueue';

        return new $class($server);
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

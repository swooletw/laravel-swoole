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
        $stub = self::hasBreakingChanges($version)
            ? __DIR__ . '/../../stubs/V57/SwooleTaskQueue.stub'
            : __DIR__ . '/../../stubs/V56/SwooleTaskQueue.stub';
        $class = static::copy($stub);

        return new $class($server);
    }

    /**
     * @param string $stub
     *
     * @return string
     */
    private static function copy(string $stub)
    {
        $destination = __DIR__ . '/SwooleTaskQueue.php';

        if (!file_exists($destination)) {
            copy($stub, $destination);
        }

        return 'SwooleTW\Http\Task\SwooleTaskQueue';
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

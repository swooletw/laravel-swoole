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
        $stub = static::stub($version);
        $class = static::copy($stub);

        return new $class($server);
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public static function stub(string $version)
    {
        return static::hasBreakingChanges($version)
            ? __DIR__ . '/../../stubs/5.6/SwooleTaskQueue.stub'
            : __DIR__ . '/../../stubs/5.7/SwooleTaskQueue.stub';
    }

    /**
     * @param string $stub
     *
     * @return string
     */
    public static function copy(string $stub)
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

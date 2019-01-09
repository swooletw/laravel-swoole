<?php

namespace SwooleTW\Http\Task;

use Illuminate\Support\Arr;
use SwooleTW\Http\Helpers\FW;
use SwooleTW\Http\Task\SwooleTaskQueue;

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
     * Swoole task queue class
     *
     * @const string
     */
    public const QUEUE_CLASS = 'SwooleTW\Http\Task\SwooleTaskQueue';

    /**
     * Swoole task queue path
     *
     * @const string
     */
    public const QUEUE_CLASS_PATH = __DIR__ . '/SwooleTaskQueue.php';

    /**
     * @param \Swoole\Http\Server $server
     * @param string $version
     *
     * @return \SwooleTW\Http\Task\SwooleTaskQueue
     */
    public static function make($server, string $version): SwooleTaskQueue
    {
        $isMatch = static::isFileVersionMatch($version);
        $class = static::copy(static::stub($version), ! $isMatch);

        return new $class($server);
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public static function stub(string $version): string
    {
        return static::hasBreakingChanges($version)
            ? __DIR__ . '/../../stubs/5.7/SwooleTaskQueue.stub'
            : __DIR__ . '/../../stubs/5.6/SwooleTaskQueue.stub';
    }

    /**
     * @param string $stub
     * @param bool $rewrite
     *
     * @return string
     */
    public static function copy(string $stub, bool $rewrite = false): string
    {
        if (! file_exists(static::QUEUE_CLASS_PATH) || $rewrite) {
            copy($stub, static::QUEUE_CLASS_PATH);
        }

        return static::QUEUE_CLASS;
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    protected static function isFileVersionMatch(string $version): bool
    {
        try {
            $fileVersion = null;
            if (class_exists(self::QUEUE_CLASS)) {
                $ref = new \ReflectionClass(self::QUEUE_CLASS);
                if (preg_match(FW::VERSION_WITHOUT_BUG_FIX, $ref->getDocComment(), $result)) {
                    $fileVersion = Arr::first($result);
                }
            }

            return version_compare($fileVersion, $version, '>=');
        } catch (\Exception $e) {
            return false;
        }
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

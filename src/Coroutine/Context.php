<?php

namespace SwooleTW\Http\Coroutine;

use Illuminate\Contracts\Container\Container;
use Swoole\Coroutine;

class Context
{
    protected const MAX_RECURSE_COROUTINE_ID = 50;

    /**
     * The app containers in different coroutine environment.
     *
     * @var array
     */
    protected static $apps = [];

    /**
     * The data in different coroutine environment.
     *
     * @var array
     */
    protected static $data = [];

    /**
     * Get app container by current coroutine id.
     */
    public static function getApp()
    {
        return static::$apps[static::getRequestedCoroutineId()] ?? null;
    }

    /**
     * Set app container by current coroutine id.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     */
    public static function setApp(Container $app)
    {
        static::$apps[static::getRequestedCoroutineId()] = $app;
    }

    /**
     * Get data by current coroutine id.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public static function getData(string $key)
    {
        return static::$data[static::getRequestedCoroutineId()][$key] ?? null;
    }

    /**
     * Set data by current coroutine id.
     *
     * @param string $key
     * @param $value
     */
    public static function setData(string $key, $value)
    {
        static::$data[static::getRequestedCoroutineId()][$key] = $value;
    }

    /**
     * Remove data by current coroutine id.
     *
     * @param string $key
     */
    public static function removeData(string $key)
    {
        unset(static::$data[static::getRequestedCoroutineId()][$key]);
    }

    /**
     * Get data keys by current coroutine id.
     */
    public static function getDataKeys()
    {
        return array_keys(static::$data[static::getRequestedCoroutineId()] ?? []);
    }

    /**
     * Clear data by current coroutine id.
     */
    public static function clear()
    {
        unset(static::$apps[static::getRequestedCoroutineId()]);
        unset(static::$data[static::getRequestedCoroutineId()]);
    }

    public static function getCoroutineId(): int
    {
        return Coroutine::getuid();
    }

    /**
     * Get current coroutine id.
     */
    public static function getRequestedCoroutineId(): int
    {
        $currentId = static::getCoroutineId();
        if ($currentId === -1) {
            return -1;
        }

        $counter = 0;
        while (($topCoroutineId = Coroutine::getPcid($currentId)) !== -1 && $counter <= static::MAX_RECURSE_COROUTINE_ID) {
            $currentId = $topCoroutineId;
            $counter++;
        }
        return $currentId;
    }
}

<?php

namespace SwooleTW\Http\Coroutine;

use Swoole\Coroutine;
use Illuminate\Contracts\Container\Container;

class Context
{
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
        return static::$apps[static::getCoroutineId()] ?? null;
    }

    /**
     * Set app container by current coroutine id.
     */
    public static function setApp(Container $app)
    {
        static::$apps[static::getCoroutineId()] = $app;
    }

    /**
     * Get data by current coroutine id.
     */
    public static function getData(string $key)
    {
        return static::$data[static::getCoroutineId()][$key] ?? null;
    }

    /**
     * Set data by current coroutine id.
     */
    public static function setData(string $key, $value)
    {
        static::$data[static::getCoroutineId()][$key] = $value;
    }

    /**
     * Remove data by current coroutine id.
     */
    public static function removeData(string $key)
    {
        unset(static::$data[static::getCoroutineId()][$key]);
    }

    /**
     * Get data by current coroutine id.
     */
    public static function getDataKeys()
    {
        return array_keys(static::$data[static::getCoroutineId()] ?? []);
    }

    /**
     * Get data by current coroutine id.
     */
    public static function clear()
    {
        unset(static::$apps[static::getCoroutineId()]);
        unset(static::$data[static::getCoroutineId()]);
    }

    /**
     * Get current coroutine id.
     */
    public static function getCoroutineId()
    {
        return Coroutine::getuid();
    }
}

<?php

/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Modifier: Albert Chen
 * License: Apache 2.0
 */

namespace SwooleTW\Http\Coroutine;

use SwooleTW\Http\Coroutine\Mysql;

class PDO
{
    /**
     * PDO constructor.
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $driverOptions
     *
     * @return SwooleTW\Http\Coroutine\Mysql
     */
    public static function construct(
        string $dsn,
        string $username = '',
        string $password = '',
        array $driverOptions = []
    ) {
        $dsn = explode(':', $dsn);
        $driver = ucwords(array_shift($dsn));
        $dsn = explode(';', implode(':', $dsn));
        $options = [];

        static::checkDriver($driver);

        foreach ($dsn as $kv) {
            $kv = explode('=', $kv);
            if ($kv) {
                $options[$kv[0]] = $kv[1] ?? '';
            }
        }

        $authorization = [
            'user' => $username,
            'password' => $password,
        ];

        $options = $driverOptions + $authorization + $options;
        $class = __NAMESPACE__ . '\\' . $driver;

        return $class::construct($options);
    }

    public static function checkDriver(string $driver)
    {
        if (! in_array($driver, static::getAvailableDrivers())) {
            throw new \InvalidArgumentException("{$driver} driver is not supported yet.");
        }
    }

    public static function getAvailableDrivers()
    {
        return ['Mysql'];
    }

}
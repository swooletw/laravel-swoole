<?php

namespace SwooleTW\Http\Coroutine\Connectors;

use Exception;
use Illuminate\Support\Str;
use SwooleTW\Http\Coroutine\PDO as SwoolePDO;
use Illuminate\Database\Connectors\MySqlConnector as BaseConnector;

class MySqlConnector extends BaseConnector
{
    /**
     * Create a new PDO connection instance.
     *
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array  $options
     * @return \PDO
     */
    protected function createPdoConnection($dsn, $username, $password, $options)
    {
        //TODO I'm thinking make it out by MysqlPool
        return new SwoolePDO($dsn, $username, $password, $options);
    }

    /**
     * Handle an exception that occurred during connect execution.
     *
     * @param  \Exception  $e
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array   $options
     * @return \PDO
     *
     * @throws \Exception
     */
    protected function tryAgainIfCausedByLostConnection(Exception $e, $dsn, $username, $password, $options)
    {
        // https://github.com/swoole/swoole-src/blob/a414e5e8fec580abb3dbd772d483e12976da708f/swoole_mysql_coro.c#L196
        if ($this->causedByLostConnection($e) || Str::contains($e->getMessage(), 'is closed')) {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * @param $connection
     * @param array $config
     * if database set timezone, the laravel/lumen frame will exec
     * $connection->prepare('set time_zone="'.$config['timezone'].'"')->execute();
     * this will occur to ERROR in "Coroutine\MySQL", Maybe swoole co::mysql's 'execute()' params
     * mustn't be empty array. My lumen is v5.5.2
     */
    protected function configureTimezone($connection, array $config)
    {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone=?')->execute([$config['timezone']]);
        }
    }
}

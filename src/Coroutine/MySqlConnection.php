<?php

namespace SwooleTW\Http\Coroutine;

use Closure;
use Illuminate\Database\MySqlConnection as BaseConnection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class MySqlConnection extends BaseConnection
{
    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param  \Illuminate\Database\QueryException $e
     * @param  string $query
     * @param  array $bindings
     * @param  \Closure $callback
     *
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        // https://github.com/swoole/swoole-src/blob/a414e5e8fec580abb3dbd772d483e12976da708f/swoole_mysql_coro.c#L1140
        if ($this->causedByLostConnection($e->getPrevious()) || Str::contains(
                $e->getMessage(),
                ['is closed', 'is not established']
            )) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }
}

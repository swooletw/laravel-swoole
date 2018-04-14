<?php

namespace SwooleTW\Http\Server;

use Swoole\Table as SwooleTable;

class Table
{
    protected $tables = [];

    public function add(string $name, SwooleTable $table)
    {
        $this->tables[$name] = $table;

        return $this;
    }

    public function get(string $name)
    {
        return $this->tables[$name] ?? null;
    }

    public function getAll()
    {
        return $this->tables;
    }
}

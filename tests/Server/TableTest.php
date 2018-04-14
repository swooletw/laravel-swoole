<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use SwooleTW\Http\Server\Table;
use Swoole\Table as SwooleTable;
use SwooleTW\Http\Tests\TestCase;

class TableTest extends TestCase
{
    public function testAdd()
    {
        $swooleTable = m::mock(SwooleTable::class);

        $table = new Table;
        $table->add($name = 'foo', $swooleTable);

        $this->assertSame($swooleTable, $table->get($name));
    }

    public function testGetAll()
    {
        $swooleTable = m::mock(SwooleTable::class);

        $table = new Table;
        $table->add($foo = 'foo', $swooleTable);
        $table->add($bar = 'bar', $swooleTable);

        $this->assertSame(2, count($table->getAll()));
        $this->assertSame($swooleTable, $table->getAll()[$foo]);
        $this->assertSame($swooleTable, $table->getAll()[$bar]);
    }
}

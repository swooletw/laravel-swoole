<?php

namespace SwooleTW\Http\Tests\Server;

use Swoole\Table;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\Rooms\TableRoom;

class TableRoomTest extends TestCase
{
    protected $tableRoom;

    public function setUp()
    {
        $config = [
            'room_rows' => 4096,
            'room_size' => 2048,
            'client_rows' => 8192,
            'client_size' => 2048
        ];
        $this->tableRoom = new TableRoom($config);
        $this->tableRoom->prepare();
    }

    public function testPrepare()
    {
        $reflection = new \ReflectionClass($this->tableRoom);
        $method = $reflection->getMethod('prepare');
        $method->invoke($this->tableRoom);

        $rooms = $reflection->getProperty('rooms');
        $rooms->setAccessible(true);

        $sids = $reflection->getProperty('sids');
        $sids->setAccessible(true);

        $this->assertInstanceOf(Table::class, $rooms->getValue($this->tableRoom));
        $this->assertInstanceOf(Table::class, $sids->getValue($this->tableRoom));
    }

    public function testInvalidTableName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->tableRoom->getValue(1, 'foo');
    }

    public function testSetValue()
    {
        $this->tableRoom->setValue($key = 1, $value = ['foo', 'bar'], $table = 'sids');

        $this->assertSame($value, $this->tableRoom->getValue($key, $table));
    }

    public function testAddAll()
    {
        $this->tableRoom->addAll($key = 1, $values = ['foo', 'bar']);

        $this->assertSame($this->encode($values), $this->tableRoom->getValue($key, $table = 'sids'));
        $this->assertSame([$key], $this->tableRoom->getValue($this->encode('foo'), 'rooms'));
        $this->assertSame([$key], $this->tableRoom->getValue($this->encode('bar'), 'rooms'));
    }

    public function testAdd()
    {
        $this->tableRoom->add($key = 1, $value = 'foo');

        $this->assertSame([$this->encode($value)], $this->tableRoom->getValue($key, $table = 'sids'));
        $this->assertSame([$key], $this->tableRoom->getValue($this->encode($value), 'rooms'));
    }

    public function testDeleteAll()
    {
        $this->tableRoom->addAll($key = 1, $values = ['foo', 'bar']);
        $this->tableRoom->deleteAll($key);

        $this->assertSame([], $this->tableRoom->getValue($key, $table = 'sids'));
        $this->assertSame([], $this->tableRoom->getValue($this->encode('foo'), 'rooms'));
        $this->assertSame([], $this->tableRoom->getValue($this->encode('bar'), 'rooms'));
    }

    public function testDelete()
    {
        $this->tableRoom->addAll($key = 1, $values = ['foo', 'bar']);
        $this->tableRoom->delete($key, 'foo');

        $this->assertSame([$this->encode('bar')], $this->tableRoom->getValue($key, $table = 'sids'));
        $this->assertSame([], $this->tableRoom->getValue($this->encode('foo'), 'rooms'));
        $this->assertSame([$key], $this->tableRoom->getValue($this->encode('bar'), 'rooms'));
    }

    public function testGetRooms()
    {
        $this->tableRoom->addAll($key = 1, $values = ['foo', 'bar']);

        $this->assertSame(
            $this->tableRoom->getValue($key, $table = 'sids'),
            $this->tableRoom->getRooms($key)
        );
    }

    public function testGetClients()
    {
        $keys = [1, 2];
        $this->tableRoom->add($keys[0], $room = 'foo');
        $this->tableRoom->add($keys[1], $room);

        $this->assertSame(
            $this->tableRoom->getValue($this->encode($room), $table = 'rooms'),
            $this->tableRoom->getClients($room)
        );
    }

    protected function encode($keys)
    {
        $reflection = new \ReflectionClass($this->tableRoom);
        $method = $reflection->getMethod('encode');
        $method->setAccessible(true);

        return $method->invokeArgs($this->tableRoom, [$keys]);
    }
}

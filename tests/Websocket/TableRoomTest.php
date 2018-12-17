<?php

namespace SwooleTW\Http\Tests\Websocket;


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
            'client_size' => 2048,
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

        $fds = $reflection->getProperty('fds');
        $fds->setAccessible(true);

        $this->assertInstanceOf(Table::class, $rooms->getValue($this->tableRoom));
        $this->assertInstanceOf(Table::class, $fds->getValue($this->tableRoom));
    }

    public function testInvalidTableName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->tableRoom->getValue(1, 'foo');
    }

    public function testSetValue()
    {
        $this->tableRoom->setValue($key = 1, $value = ['foo', 'bar'], $table = 'fds');

        $this->assertSame($value, $this->tableRoom->getValue($key, $table));
    }

    public function testAddAll()
    {
        $this->tableRoom->add($key = 1, $values = ['foo', 'bar']);

        $this->assertSame($values, $this->tableRoom->getValue($key, $table = 'fds'));
        $this->assertSame([$key], $this->tableRoom->getValue('foo', 'rooms'));
        $this->assertSame([$key], $this->tableRoom->getValue('bar', 'rooms'));
    }

    public function testAdd()
    {
        $this->tableRoom->add($key = 1, $value = 'foo');

        $this->assertSame([$value], $this->tableRoom->getValue($key, $table = 'fds'));
        $this->assertSame([$key], $this->tableRoom->getValue($value, 'rooms'));
    }

    public function testDeleteAll()
    {
        $this->tableRoom->add($key = 1, $values = ['foo', 'bar']);
        $this->tableRoom->delete($key);

        $this->assertSame([], $this->tableRoom->getValue($key, $table = 'fds'));
        $this->assertSame([], $this->tableRoom->getValue('foo', 'rooms'));
        $this->assertSame([], $this->tableRoom->getValue('bar', 'rooms'));
    }

    public function testDelete()
    {
        $this->tableRoom->add($key = 1, $values = ['foo', 'bar']);
        $this->tableRoom->delete($key, 'foo');

        $this->assertSame(['bar'], $this->tableRoom->getValue($key, $table = 'fds'));
        $this->assertSame([], $this->tableRoom->getValue('foo', 'rooms'));
        $this->assertSame([$key], $this->tableRoom->getValue('bar', 'rooms'));
    }

    public function testGetRooms()
    {
        $this->tableRoom->add($key = 1, $values = ['foo', 'bar']);

        $this->assertSame(
            $this->tableRoom->getValue($key, $table = 'fds'),
            $this->tableRoom->getRooms($key)
        );
    }

    public function testGetClients()
    {
        $keys = [1, 2];
        $this->tableRoom->add($keys[0], $room = 'foo');
        $this->tableRoom->add($keys[1], $room);

        $this->assertSame(
            $this->tableRoom->getValue($room, $table = 'rooms'),
            $this->tableRoom->getClients($room)
        );
    }
}

<?php

namespace SwooleTW\Http\Websocket\Rooms;

use Swoole\Table;
use SwooleTW\Http\Websocket\Rooms\RoomContract;

class TableRoom implements RoomContract
{
    protected $config;

    protected $rooms;

    protected $fds;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare()
    {
        $this->initRoomsTable();
        $this->initFdsTable();
    }

    public function add(int $fd, $roomNames)
    {
        $rooms = $this->getRooms($fd);
        $roomNames = is_array($roomNames) ? $roomNames : [$roomNames];

        foreach ($roomNames as $room) {
            $fds = $this->getClients($room);

            if (in_array($fd, $fds)) {
                continue;
            }

            $fds[] = $fd;
            $rooms[] = $room;

            $this->setClients($room, $fds);
        }

        $this->setRooms($fd, $rooms);
    }

    public function delete(int $fd, $roomNames = [])
    {
        $allRooms = $this->getRooms($fd);
        $roomNames = is_array($roomNames) ? $roomNames : [$roomNames];
        $rooms = count($roomNames) ? $roomNames : $allRooms;

        $removeRooms = [];
        foreach ($rooms as $room) {
            $fds = $this->getClients($room);

            if (! in_array($fd, $fds)) {
                continue;
            }

            $this->setClients($room, array_values(array_diff($fds, [$fd])), 'rooms');
            $removeRooms[] = $room;
        }

        $this->setRooms($fd, array_values(array_diff($allRooms, $removeRooms)), 'fds');
    }

    public function getClients(string $room)
    {
        return $this->getValue($room, 'rooms');
    }

    public function getRooms(int $fd)
    {
        return $this->getValue($fd, 'fds');
    }

    protected function setClients(string $room, array $fds)
    {
        return $this->setValue($room, $fds, 'rooms');
    }

    protected function setRooms(int $fd, array $rooms)
    {
        return $this->setValue($fd, $rooms, 'fds');
    }

    protected function initRoomsTable()
    {
        $this->rooms = new Table($this->config['room_rows']);
        $this->rooms->column('value', Table::TYPE_STRING, $this->config['room_size']);
        $this->rooms->create();
    }

    protected function initFdsTable()
    {
        $this->fds = new Table($this->config['client_rows']);
        $this->fds->column('value', Table::TYPE_STRING, $this->config['client_size']);
        $this->fds->create();
    }

    public function setValue($key, array $value, string $table)
    {
        $this->checkTable($table);

        $this->$table->set($key, [
            'value' => json_encode($value)
        ]);

        return $this;
    }

    public function getValue(string $key, string $table)
    {
        $this->checkTable($table);

        $value = $this->$table->get($key);

        return $value ? json_decode($value['value'], true) : [];
    }

    protected function checkTable(string $table)
    {
        if (! property_exists($this, $table) || ! $this->$table instanceof Table) {
            throw new \InvalidArgumentException('Invalid table name.');
        }
    }
}

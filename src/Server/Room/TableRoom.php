<?php

namespace SwooleTW\Http\Server\Room;

use Swoole\Table;
use SwooleTW\Http\Server\Room\RoomContract;

class TableRoom implements RoomContract
{
    protected $config;

    protected $rooms;

    protected $sids;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare()
    {
        $this->initRoomsTable();
        $this->initSidsTable();
    }

    public function add(int $fd, string $room)
    {
        $this->addAll($fd, [$room]);
    }

    public function addAll(int $fd, array $roomNames)
    {
        $rooms = $this->getRooms($fd);

        foreach ($roomNames as $room) {
            $room = $this->encode($room);
            $sids = $this->getClients($room, false);

            if (in_array($fd, $sids)) {
                continue;
            }

            $sids[] = $fd;
            $rooms[] = $room;

            $this->setValue($room, $sids, 'rooms');
        }

        $this->setValue($fd, $rooms, 'sids');
    }

    public function delete(int $fd, string $room)
    {
        $this->deleteAll($fd, [$room]);
    }

    public function deleteAll(int $fd, array $roomNames = [])
    {
        $allRooms = $this->getRooms($fd);
        $rooms = count($roomNames) ? $this->encode($roomNames) : $allRooms;

        $removeRooms = [];
        foreach ($rooms as $room) {
            $sids = $this->getClients($room, false);

            if (! in_array($fd, $sids)) {
                continue;
            }

            $this->setValue($room, array_values(array_diff($sids, [$fd])), 'rooms');
            $removeRooms[] = $room;
        }

        $this->setValue($fd, array_values(array_diff($allRooms, $removeRooms)), 'sids');
    }

    public function getClients(string $room, $hash = true)
    {
        if ($hash) {
            $room = $this->encode($room);
        }

        return $this->getValue($room, 'rooms');
    }

    public function getRooms(int $fd)
    {
        return $this->getValue($fd, 'sids');
    }

    protected function initRoomsTable()
    {
        $this->rooms = new Table($this->config['room_rows']);
        $this->rooms->column('value', Table::TYPE_STRING, $this->config['room_size']);
        $this->rooms->create();
    }

    protected function initSidsTable()
    {
        $this->sids = new Table($this->config['client_rows']);
        $this->sids->column('value', Table::TYPE_STRING, $this->config['client_size']);
        $this->sids->create();
    }

    protected function encode($keys)
    {
        if (is_array($keys)) {
            $result = [];
            foreach ($keys as $value) {
                $result[] = md5($value);
            }

            return $result;
        }

        return md5($keys);
    }

    public function setValue($key, array $value, $table)
    {
        $this->checkTable($table);

        $this->$table->set($key, [
            'value' => json_encode($value)
        ]);
    }

    public function getValue($key, $table)
    {
        $this->checkTable($table);

        $value = $this->$table->get($key);

        return $value ? json_decode($value['value'], true) : [];
    }

    protected function checkTable($table)
    {
        if (! property_exists($this, $table) || ! $this->$table instanceof Table) {
            throw new \InvalidArgumentException('invalid table name.');
        }
    }
}

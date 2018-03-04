<?php

namespace SwooleTW\Http\Server\Room;

use Swoole\Table;
use SwooleTW\Http\Server\Room\RoomContract;

class TableRoom implements RoomContract
{
    protected $rooms;

    protected $sids;

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
        $this->rooms = new Table(2048);
        $this->rooms->column('value', Table::TYPE_STRING, 2048);
        $this->rooms->create();
    }

    protected function initSidsTable()
    {
        $this->sids = new Table(8192);
        $this->sids->column('value', Table::TYPE_STRING, 2048);
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

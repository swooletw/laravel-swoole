<?php

namespace SwooleTW\Http\Websocket\Rooms;

use Illuminate\Redis\RedisManager;
use SwooleTW\Http\Websocket\Rooms\RoomContract;

class RedisRoom implements RoomContract
{
    const PREFIX = 'swoole:';

    protected $redis;

    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare()
    {
        $this->setRedis();
    }

    /**
     * Set redis manager provided by illuminate redis.
     */
    public function setRedis(RedisManager $redis = null)
    {
        if ($redis) {
            $this->redis = $redis;
        } else {
            $config = $this->config;
            $client = $config['client'] ?? 'predis';
            unset($config['client']);

            $this->redis = new RedisManager($client, $config);
        }
    }

    /**
     * Get redis manager.
     */
    public function getRedis()
    {
        return $this->redis;
    }

    public function add(int $fd, string $room)
    {
        $this->addAll($fd, [$room]);
    }

    public function addAll(int $fd, array $roomNames)
    {
        $this->addValue($fd, $roomNames, 'sids');

        foreach ($roomNames as $room) {
            $this->addValue($room, [$fd], 'rooms');
        }
    }

    public function delete(int $fd, string $room)
    {
        $this->deleteAll($fd, [$room]);
    }

    public function deleteAll(int $fd, array $roomNames = [])
    {
        $this->removeValue($fd, $roomNames, 'sids');

        foreach ($roomNames as $room) {
            $this->removeValue($room, [$fd], 'rooms');
        }
    }

    public function addValue($key, array $values, string $table)
    {
        $this->checkTable($table);
        $redisKey = $this->getKey($key, $table);

        foreach ($values as $value) {
            $this->redis->sadd($redisKey, $value);
        }

        return $this;
    }

    public function removeValue($key, array $values, string $table)
    {
        $this->checkTable($table);
        $redisKey = $this->getKey($key, $table);

        foreach ($values as $value) {
            $this->redis->srem($redisKey, $value);
        }

        return $this;
    }

    public function getClients(string $room)
    {
        return $this->getValue($room, 'rooms');
    }

    public function getRooms(int $fd)
    {
        return $this->getValue($fd, 'sids');
    }

    protected function checkTable(string $table)
    {
        if (! in_array($table, ['rooms', 'sids'])) {
            throw new \InvalidArgumentException('invalid table name.');
        }
    }

    public function getValue(string $key, string $table)
    {
        $this->checkTable($table);

        return $this->redis->get($this->getKey($key, $table));
    }

    public function getKey(string $key, string $table)
    {
        return static::PREFIX . "{$table}:{$key}";
    }
}

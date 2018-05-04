<?php

namespace SwooleTW\Http\Websocket\Rooms;

use Predis\Client as RedisClient;
use SwooleTW\Http\Websocket\Rooms\RoomContract;

class RedisRoom implements RoomContract
{
    protected $redis;

    protected $config;

    protected $prefix = 'swoole:';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare(RedisClient $redis = null)
    {
        $this->setRedis($redis);
        $this->setPrefix();
        $this->cleanRooms();
    }

    /**
     * Set redis client.
     */
    public function setRedis(RedisClient $redis = null)
    {
        $server = $this->config['server'] ?? [];
        $options = $this->config['options'] ?? [];

        // forbid setting prefix from options
        if (array_key_exists('prefix', $options)) {
            unset($options['prefix']);
        }

        if ($redis) {
            $this->redis = $redis;
        } else {
            $this->redis = new RedisClient($server, $options);
        }
    }

    /**
     * Set key prefix from config.
     */
    protected function setPrefix()
    {
        if (array_key_exists('prefix', $this->config)) {
            $this->prefix = $this->config['prefix'];
        }
    }

    /**
     * Get redis client.
     */
    public function getRedis()
    {
        return $this->redis;
    }

    public function add(int $fd, $roomNames)
    {
        $roomNames = is_array($roomNames) ? $roomNames : [$roomNames];

        $this->addValue($fd, $roomNames, 'fds');

        foreach ($roomNames as $room) {
            $this->addValue($room, [$fd], 'rooms');
        }
    }

    public function delete(int $fd, $roomNames = [])
    {
        $roomNames = is_array($roomNames) ? $roomNames : [$roomNames];
        $roomNames = count($roomNames) ? $roomNames : $this->getRooms($fd);

        $this->removeValue($fd, $roomNames, 'fds');

        foreach ($roomNames as $room) {
            $this->removeValue($room, [$fd], 'rooms');
        }
    }

    public function addValue($key, array $values, string $table)
    {
        $this->checkTable($table);
        $redisKey = $this->getKey($key, $table);

        $this->redis->pipeline(function ($pipe) use ($redisKey, $values) {
            foreach ($values as $value) {
                $pipe->sadd($redisKey, $value);
            }
        });

        return $this;
    }

    public function removeValue($key, array $values, string $table)
    {
        $this->checkTable($table);
        $redisKey = $this->getKey($key, $table);

        $this->redis->pipeline(function ($pipe) use ($redisKey, $values) {
            foreach ($values as $value) {
                $pipe->srem($redisKey, $value);
            }
        });

        return $this;
    }

    public function getClients(string $room)
    {
        return $this->getValue($room, 'rooms');
    }

    public function getRooms(int $fd)
    {
        return $this->getValue($fd, 'fds');
    }

    protected function checkTable(string $table)
    {
        if (! in_array($table, ['rooms', 'fds'])) {
            throw new \InvalidArgumentException('invalid table name.');
        }
    }

    public function getValue(string $key, string $table)
    {
        $this->checkTable($table);

        return $this->redis->smembers($this->getKey($key, $table));
    }

    public function getKey(string $key, string $table)
    {
        return "{$this->prefix}{$table}:{$key}";
    }

    protected function cleanRooms()
    {
        $keys = $this->redis->keys("{$this->prefix}*");
        if (count($keys)) {
            $this->redis->del($keys);
        }
    }
}

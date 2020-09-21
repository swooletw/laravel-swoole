<?php

namespace SwooleTW\Http\Websocket\Rooms;

use Illuminate\Support\Arr;
use Predis\Client as RedisClient;
use Predis\Pipeline\Pipeline;

/**
 * Class RedisRoom
 */
class RedisRoom implements RoomContract
{
    /**
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $prefix = 'swoole:';

    /**
     * RedisRoom constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Predis\Client|null $redis
     *
     * @return \SwooleTW\Http\Websocket\Rooms\RoomContract
     */
    public function prepare(RedisClient $redis = null): RoomContract
    {
        $this->setRedis($redis);
        $this->setPrefix();
        $this->cleanRooms();

        return $this;
    }

    /**
     * Set redis client.
     *
     * @param \Predis\Client|null $redis
     */
    public function setRedis(?RedisClient $redis = null)
    {
        if (! $redis) {
            $server = Arr::get($this->config, 'server', []);
            $options = Arr::get($this->config, 'options', []);

            // forbid setting prefix from options
            if (Arr::has($options, 'prefix')) {
                $options = Arr::except($options, 'prefix');
            }

            $redis = new RedisClient($server, $options);
        }

        $this->redis = $redis;
    }

    /**
     * Set key prefix from config.
     */
    protected function setPrefix()
    {
        if ($prefix = Arr::get($this->config, 'prefix')) {
            $this->prefix = $prefix;
        }
    }

    /**
     * Get redis client.
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Add multiple socket fds to a room.
     *
     * @param int fd
     * @param array|string rooms
     */
    public function add(int $fd, $rooms)
    {
        $rooms = is_array($rooms) ? $rooms : [$rooms];

        $this->addValue($fd, $rooms, RoomContract::DESCRIPTORS_KEY);

        foreach ($rooms as $room) {
            $this->addValue($room, [$fd], RoomContract::ROOMS_KEY);
        }
    }

    /**
     * Delete multiple socket fds from a room.
     *
     * @param int fd
     * @param array|string rooms
     */
    public function delete(int $fd, $rooms)
    {
        $rooms = is_array($rooms) ? $rooms : [$rooms];
        $rooms = count($rooms) ? $rooms : $this->getRooms($fd);

        $this->removeValue($fd, $rooms, RoomContract::DESCRIPTORS_KEY);

        foreach ($rooms as $room) {
            $this->removeValue($room, [$fd], RoomContract::ROOMS_KEY);
        }
    }

    /**
     * Add value to redis.
     *
     * @param $key
     * @param array $values
     * @param string $table
     *
     * @return $this
     */
    public function addValue($key, array $values, string $table)
    {
        $this->checkTable($table);
        $redisKey = $this->getKey($key, $table);

        $this->redis->pipeline(function (Pipeline $pipe) use ($redisKey, $values) {
            foreach ($values as $value) {
                $pipe->sadd($redisKey, $value);
            }
        });

        return $this;
    }

    /**
     * Remove value from redis.
     *
     * @param $key
     * @param array $values
     * @param string $table
     *
     * @return $this
     */
    public function removeValue($key, array $values, string $table)
    {
        $this->checkTable($table);
        $redisKey = $this->getKey($key, $table);

        $this->redis->pipeline(function (Pipeline $pipe) use ($redisKey, $values) {
            foreach ($values as $value) {
                $pipe->srem($redisKey, $value);
            }
        });

        return $this;
    }

    /**
     * Get all sockets by a room key.
     *
     * @param string room
     *
     * @return array
     */
    public function getClients(string $room)
    {
        return $this->getValue($room, RoomContract::ROOMS_KEY) ?? [];
    }

    /**
     * Get all rooms by a fd.
     *
     * @param int fd
     *
     * @return array
     */
    public function getRooms(int $fd)
    {
        return $this->getValue($fd, RoomContract::DESCRIPTORS_KEY) ?? [];
    }

    /**
     * Check table for rooms and descriptors.
     *
     * @param string $table
     */
    protected function checkTable(string $table)
    {
        if (! in_array($table, [RoomContract::ROOMS_KEY, RoomContract::DESCRIPTORS_KEY])) {
            throw new \InvalidArgumentException("Invalid table name: `{$table}`.");
        }
    }

    /**
     * Get value.
     *
     * @param string $key
     * @param string $table
     *
     * @return array
     */
    public function getValue(string $key, string $table)
    {
        $this->checkTable($table);

        $result = $this->redis->smembers($this->getKey($key, $table));

        // Try to fix occasional non-array returned result
        return is_array($result) ? $result : [];
    }

    /**
     * Get key.
     *
     * @param string $key
     * @param string $table
     *
     * @return string
     */
    public function getKey(string $key, string $table)
    {
        return "{$this->prefix}{$table}:{$key}";
    }

    /**
     * Clean all rooms.
     */
    protected function cleanRooms(): void
    {
        if (count($keys = $this->redis->keys("{$this->prefix}*"))) {
            $this->redis->del($keys);
        }
    }
}

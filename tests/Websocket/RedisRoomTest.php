<?php

namespace SwooleTW\Http\Tests\Websocket;

use Mockery as m;
use Predis\Client as RedisClient;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\Rooms\RedisRoom;

class RedisRoomTest extends TestCase
{
    public function testPrepare()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('keys')
              ->once()
              ->andReturn($keys = ['foo', 'bar']);
        $redis->shouldReceive('del')
              ->with($keys)
              ->once();

        $redisRoom = new RedisRoom([]);
        $redisRoom->prepare($redis);

        $this->assertTrue($redisRoom->getRedis() instanceOf RedisClient);
    }

    public function testInvalidTableName()
    {
        $redisRoom = $this->getRedisRoom();

        $this->expectException(\InvalidArgumentException::class);

        $redisRoom->getValue(1, 'foo');
    }

    public function testAddValue()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('pipeline')
              ->once();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->addValue(1, ['foo', 'bar'], 'fds');
    }

    public function testAddAll()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('pipeline')
              ->times(3);
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->add(1, ['foo', 'bar']);
    }

    public function testAdd()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('pipeline')
              ->twice();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->add(1, 'foo');
    }

    public function testDeleteAll()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('pipeline')
              ->times(3);
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->delete(1, ['foo', 'bar']);
    }

    public function testDelete()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('pipeline')
              ->twice();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->delete(1, 'foo');
    }

    public function testGetRooms()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('smembers')
              ->with('swoole:fds:1')
              ->once();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->getRooms(1);
    }

    public function testGetClients()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('smembers')
              ->with('swoole:rooms:foo')
              ->once();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->getClients('foo');
    }

    protected function getRedisRoom($redis = null)
    {
        $redisRoom = new RedisRoom([]);
        $redisRoom->setRedis($redis ?: $this->getRedis());

        return $redisRoom;
    }

    protected function getRedis()
    {
        $redis = m::mock(RedisClient::class);

        return $redis;
    }
}

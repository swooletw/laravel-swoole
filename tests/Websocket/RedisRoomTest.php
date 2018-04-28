<?php

namespace SwooleTW\Http\Tests\Websocket;

use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use Predis\Client as RedisClient;
use SwooleTW\Http\Websocket\Rooms\RedisRoom;

class RedisRoomTest extends TestCase
{
    public function testPrepare()
    {
        $redisRoom = new RedisRoom([]);
        $redisRoom->prepare();

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
        $redis->shouldReceive('sadd')
            ->with('swoole:sids:1', m::type('string'))
            ->twice();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->addValue(1, ['foo', 'bar'], 'sids');
    }

    public function testAddAll()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('sadd')
            ->with('swoole:sids:1', m::type('string'))
            ->twice();
        $redis->shouldReceive('sadd')
            ->with('swoole:rooms:foo', $fd = 1)
            ->once();
        $redis->shouldReceive('sadd')
            ->with('swoole:rooms:bar', $fd)
            ->once();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->addAll($fd, ['foo', 'bar']);
    }

    public function testAdd()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('sadd')
            ->with('swoole:sids:1', m::type('string'))
            ->once();
        $redis->shouldReceive('sadd')
            ->with('swoole:rooms:foo', $fd = 1)
            ->once();
        $redisRoom = $this->getRedisRoom($redis);
        $redisRoom->add($fd, 'foo');
    }

    public function testDeleteAll()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('srem')
            ->with('swoole:sids:1', m::type('string'))
            ->twice();
        $redis->shouldReceive('srem')
            ->with('swoole:rooms:foo', $fd = 1)
            ->once();
        $redis->shouldReceive('srem')
            ->with('swoole:rooms:bar', $fd)
            ->once();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->deleteAll($fd, ['foo', 'bar']);
    }

    public function testDelete()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('srem')
            ->with('swoole:sids:1', m::type('string'))
            ->once();
        $redis->shouldReceive('srem')
            ->with('swoole:rooms:foo', $fd = 1)
            ->once();
        $redisRoom = $this->getRedisRoom($redis);
        $redisRoom->delete($fd, 'foo');
    }

    public function testGetRooms()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('get')
            ->with('swoole:sids:1')
            ->once();
        $redisRoom = $this->getRedisRoom($redis);

        $redisRoom->getRooms(1);
    }

    public function testGetClients()
    {
        $redis = $this->getRedis();
        $redis->shouldReceive('get')
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

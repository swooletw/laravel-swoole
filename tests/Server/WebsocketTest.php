<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Server\Websocket\Websocket;
use SwooleTW\Http\Server\Websocket\Rooms\RoomContract;

class WebsocketTest extends TestCase
{
    public function setUp()
    {
        //
    }

    public function testSetBroadcast()
    {
        $websocket = $this->getWebsocket();
        $this->assertFalse($websocket->getIsBroadcast());

        $websocket->broadcast();
        $this->assertTrue($websocket->getIsBroadcast());
    }

    public function testSetTo()
    {
        $websocket = $this->getWebsocket()->to($foo = 'foo');
        $this->assertTrue(in_array($foo, $websocket->getTo()));

        $websocket->toAll($bar = ['foo', 'bar', 'seafood']);
        $this->assertSame($bar, $websocket->getTo());
    }

    public function testSetSender()
    {
        $websocket = $this->getWebsocket()->setSender($fd = 1);
        $this->assertSame($fd, $websocket->getSender());
    }

    public function testJoin()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('add')
            ->with($sender = 1, $name = 'room')
            ->once();

        $websocket = $this->getWebsocket($room)
            ->setSender($sender)
            ->join($name);
    }

    public function testJoinAll()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('addAll')
            ->with($sender = 1, $names = ['room1', 'room2'])
            ->once();

        $websocket = $this->getWebsocket($room)
            ->setSender($sender)
            ->joinAll($names);
    }

    public function testLeave()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('delete')
            ->with($sender = 1, $name = 'room')
            ->once();

        $websocket = $this->getWebsocket($room)
            ->setSender($sender)
            ->leave($name);
    }

    public function testLeaveAll()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('deleteAll')
            ->with($sender = 1, $names = ['room1', 'room2'])
            ->once();

        $websocket = $this->getWebsocket($room)
            ->setSender($sender)
            ->leaveAll($names);
    }

    protected function getWebsocket(RoomContract $room = null)
    {
        return new Websocket($room ?? m::mock(RoomContract::class));
    }
}

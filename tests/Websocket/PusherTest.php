<?php

namespace SwooleTW\Http\Tests\Websocket;

use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\Pusher;

class PusherTest extends TestCase
{
    public function testMake()
    {
        $data = [
            'opcode' => 1,
            'sender' => 3,
            'fds' => [1, 2],
            'broadcast' => true,
            'assigned' => true,
            'event' => 'event',
            'message' => 'message'
        ];
        $pusher = Pusher::make($data, null);

        $this->assertInstanceOf(Pusher::class, $pusher);
        $this->assertSame($data['opcode'], $pusher->getOpCode());
        $this->assertSame($data['sender'], $pusher->getSender());
        $this->assertSame($data['fds'], $pusher->getDescriptors());
        $this->assertSame($data['opcode'], $pusher->getOpCode());
        $this->assertSame($data['broadcast'], $pusher->isBroadCast());
        $this->assertSame($data['assigned'], $pusher->isAssigned());
        $this->assertSame($data['event'], $pusher->getEvent());
        $this->assertSame($data['message'], $pusher->getMessage());
    }

    public function testAddDescriptor()
    {
        $pusher = Pusher::make([
            'opcode' => 1,
            'sender' => 3,
            'fds' => [1, 2],
            'broadcast' => true,
            'assigned' => true,
            'event' => 'event',
            'message' => 'message'
        ], null);

        $pusher->addDescriptor(3);
        $this->assertSame([1, 2, 3], $pusher->getDescriptors());

        $pusher->addDescriptor(1);
        $this->assertSame([1, 2, 3], $pusher->getDescriptors());
    }

    public function testAddDescriptors()
    {
        $pusher = Pusher::make([
            'opcode' => 1,
            'sender' => 3,
            'fds' => [1, 2],
            'broadcast' => true,
            'assigned' => true,
            'event' => 'event',
            'message' => 'message'
        ], null);

        $pusher->addDescriptors([3]);
        $this->assertSame([1, 2, 3], $pusher->getDescriptors());

        $pusher->addDescriptors([1, 4]);
        $this->assertSame([1, 2, 3, 4], $pusher->getDescriptors());
    }

    public function testHasDescriptor()
    {
        $pusher = Pusher::make([
            'opcode' => 1,
            'sender' => 3,
            'fds' => [1, 2],
            'broadcast' => true,
            'assigned' => true,
            'event' => 'event',
            'message' => 'message'
        ], null);

        $this->assertTrue($pusher->hasDescriptor(1));
        $this->assertTrue($pusher->hasDescriptor(2));
        $this->assertFalse($pusher->hasDescriptor(3));
    }

    public function testShouldBroadcast()
    {
        $pusher = Pusher::make([
            'opcode' => 1,
            'sender' => 1,
            'fds' => [],
            'broadcast' => true,
            'assigned' => false,
            'event' => 'event',
            'message' => 'message'
        ], null);

        $this->assertTrue($pusher->shouldBroadcast());
    }

    public function testShouldPushToDescriptor()
    {
        $server = m::mock(Server::class);
        $server->shouldReceive('isEstablished')
            ->with($fd = 1)
            ->times(3)
            ->andReturn(true);

        $pusher = Pusher::make([
            'opcode' => 1,
            'sender' => 3,
            'fds' => [],
            'broadcast' => true,
            'assigned' => false,
            'event' => 'event',
            'message' => 'message'
        ], $server);

        $this->assertTrue($pusher->shouldPushToDescriptor($fd));

        $pusher = Pusher::make([
            'opcode' => 1,
            'sender' => 1,
            'fds' => [],
            'broadcast' => true,
            'assigned' => false,
            'event' => 'event',
            'message' => 'message'
        ], $server);

        $this->assertFalse($pusher->shouldPushToDescriptor($fd));

        $pusher = Pusher::make([
            'opcode' => 1,
            'sender' => 1,
            'fds' => [],
            'broadcast' => false,
            'assigned' => false,
            'event' => 'event',
            'message' => 'message'
        ], $server);

        $this->assertTrue($pusher->shouldPushToDescriptor($fd));
    }
}

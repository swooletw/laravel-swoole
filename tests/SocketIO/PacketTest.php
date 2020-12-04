<?php

namespace SwooleTW\Http\Tests\SocketIO;

use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\SocketIO\Packet;

class PacketTest extends TestCase
{
    public function testGetSocketType()
    {
        $type = 2;
        $packet = $type . 'probe';
        $this->assertSame($type, Packet::getSocketType($packet));

        $type = 3;
        $packet = $type . 'probe';
        $this->assertSame($type, Packet::getSocketType($packet));
    }

    public function testGetNullSocketType()
    {
        $type = 7;
        $packet = $type . 'probe';
        $this->assertNull(Packet::getSocketType($packet));

        $packet = '';
        $this->assertNull(Packet::getSocketType($packet));
    }

    public function testGetPayload()
    {
        $packet = '42["foo","bar"]';
        $this->assertSame([
            'event' => 'foo',
            'data' => 'bar',
        ], Packet::getPayload($packet));

        $packet = '42["foo", "bar"]';
        $this->assertSame([
            'event' => 'foo',
            'data' => 'bar',
        ], Packet::getPayload($packet));

        $packet = '42["foo",{"message":"test"}]';
        $this->assertSame([
            'event' => 'foo',
            'data' => [
                'message' => 'test',
            ],
        ], Packet::getPayload($packet));

        $packet = '42["foo", {"message":"test"}]';
        $this->assertSame([
            'event' => 'foo',
            'data' => [
                'message' => 'test',
            ],
        ], Packet::getPayload($packet));

        $packet = '42["foo"]';
        $this->assertSame([
            'event' => 'foo',
            'data' => null,
        ], Packet::getPayload($packet));
    }

    public function testGetNullPayload()
    {
        $packet = '';
        $this->assertNull(Packet::getPayload($packet));

        $packet = '2probe';
        $this->assertNull(Packet::getPayload($packet));
    }

    public function testIsSocketType()
    {
        $packet = '2probe';
        $this->assertTrue(Packet::isSocketType($packet, 'ping'));

        $packet = '3probe';
        $this->assertTrue(Packet::isSocketType($packet, 'pong'));

        $packet = '42["foo", "bar"]';
        $this->assertTrue(Packet::isSocketType($packet, 'message'));

        $packet = '0probe';
        $this->assertFalse(Packet::isSocketType($packet, 'ping'));
    }
}

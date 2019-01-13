<?php

namespace SwooleTW\Http\Tests\SocketIO;

use Illuminate\Support\Facades\App;
use Mockery as m;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\SocketIO\SocketIOParser;
use SwooleTW\Http\Websocket\SocketIO\Strategies\HeartbeatStrategy;

class ParserTest extends TestCase
{
    protected static $server;

    public function testEncode()
    {
        $event = 'foo';
        $data = 'bar';

        $parser = new SocketIOParser;
        $this->assertSame('42["foo","bar"]', $parser->encode($event, $data));

        $data = ['message' => 'test'];
        $this->assertSame('42["foo",{"message":"test"}]', $parser->encode($event, $data));

        $data = (object) ['message' => 'test'];
        $this->assertSame('42["foo",{"message":"test"}]', $parser->encode($event, $data));
    }

    public function testDecode()
    {
        $payload = '42["foo",{"message":"test"}]';
        $frame = m::mock(Frame::class);
        $frame->data = $payload;

        $parser = new SocketIOParser;
        $this->assertSame([
            'event' => 'foo',
            'data' => [
                'message' => 'test',
            ],
        ], $parser->decode($frame));

        $payload = '42["foo","bar"]';
        $frame->data = $payload;
        $this->assertSame([
            'event' => 'foo',
            'data' => 'bar',
        ], $parser->decode($frame));
    }

    public function testExecute()
    {
        $frame = m::mock(Frame::class);

        $app = App::shouldReceive('call')->once();

        $parser = new SocketIOParser;
        $skip = $parser->execute('server', $frame);
    }

    public function testHeartbeatStrategy()
    {
        $payload = '42["foo","bar"]';

        $frame = m::mock(Frame::class);
        $frame->data = $payload;
        $frame->fd = 1;

        $server = m::mock('server');
        $server->shouldReceive('push')->once();

        $strategy = new HeartbeatStrategy;
        $this->assertFalse($strategy->handle($server, $frame));

        $frame->data = '3';
        $this->assertTrue($strategy->handle($server, $frame));

        $frame->data = '2probe';
        $this->assertTrue($strategy->handle($server, $frame));
    }
}

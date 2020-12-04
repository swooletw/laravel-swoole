<?php

namespace SwooleTW\Http\Tests\Websocket;

use Mockery as m;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Pipeline\Pipeline;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use SwooleTW\Http\Websocket\Websocket;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class WebsocketTest extends TestCase
{
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

        $websocket->to($bar = ['foo', 'bar', 'seafood']);
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
             ->with($sender = 1, $name = ['room'])
             ->once();

        $websocket = $this->getWebsocket($room)
                          ->setSender($sender)
                          ->join($name);
    }

    public function testInAlias()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('add')
             ->with($sender = 1, $name = ['room'])
             ->once();

        $websocket = $this->getWebsocket($room)
                          ->setSender($sender)
                          ->in($name);
    }

    public function testJoinAll()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('add')
             ->with($sender = 1, $names = ['room1', 'room2'])
             ->once();

        $websocket = $this->getWebsocket($room)
                          ->setSender($sender)
                          ->join($names);
    }

    public function testLeave()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('delete')
             ->with($sender = 1, $name = ['room'])
             ->once();

        $websocket = $this->getWebsocket($room)
                          ->setSender($sender)
                          ->leave($name);
    }

    public function testLeaveAll()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('delete')
             ->with($sender = 1, $names = ['room1', 'room2'])
             ->once();

        $websocket = $this->getWebsocket($room)
                          ->setSender($sender)
                          ->leave($names);
    }

    public function testCallbacks()
    {
        $websocket = $this->getWebsocket();

        $websocket->on('foo', function () {
            return 'bar';
        });

        $this->assertTrue($websocket->eventExists('foo'));
        $this->assertFalse($websocket->eventExists('bar'));

        $this->expectException(InvalidArgumentException::class);
        $websocket->on('invalid', 123);
    }

    public function testLoginUsing()
    {
        $user = m::mock(AuthenticatableContract::class);
        $user->shouldReceive('getAuthIdentifier')
             ->once()
             ->andReturn($id = 1);

        $room = m::mock(RoomContract::class);
        $room->shouldReceive('add')
             ->with($sender = 1, ['uid_1'])
             ->once();

        $websocket = $this->getWebsocket($room)
                          ->setSender($sender)
                          ->loginUsing($user);
    }

    public function testLoginUsingId()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('add')
             ->with($sender = 1, ['uid_1'])
             ->once();

        $websocket = $this->getWebsocket($room)
                          ->setSender($sender)
                          ->loginUsingId(1);
    }

    public function testToUserId()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('getClients')
             ->with('uid_1')
             ->once()
             ->andReturn([$uid = 1]);

        $websocket = $this->getWebsocket($room)->toUserId($uid);
        $this->assertTrue(in_array($uid, $websocket->getTo()));

        $room->shouldReceive('getClients')
             ->with('uid_2')
             ->once()
             ->andReturn([2]);
        $room->shouldReceive('getClients')
             ->with('uid_3')
             ->once()
             ->andReturn([3]);

        $websocket->toUserId([2, 3]);
        $this->assertTrue(in_array(2, $websocket->getTo()));
        $this->assertTrue(in_array(3, $websocket->getTo()));
    }

    public function testToUser()
    {
        $user = m::mock(AuthenticatableContract::class);
        $user->shouldReceive('getAuthIdentifier')
             ->once()
             ->andReturn($uid = 1);

        $room = m::mock(RoomContract::class);
        $room->shouldReceive('getClients')
             ->with('uid_1')
             ->once()
             ->andReturn([$uid]);

        $websocket = $this->getWebsocket($room)->toUser($user);
        $this->assertTrue(in_array($uid, $websocket->getTo()));

        $room->shouldReceive('getClients')
             ->with('uid_2')
             ->once()
             ->andReturn([2]);
        $room->shouldReceive('getClients')
             ->with('uid_3')
             ->once()
             ->andReturn([3]);

        $userA = m::mock(AuthenticatableContract::class);
        $userA->shouldReceive('getAuthIdentifier')
              ->once()
              ->andReturn(2);
        $userB = m::mock(AuthenticatableContract::class);
        $userB->shouldReceive('getAuthIdentifier')
              ->once()
              ->andReturn(3);

        $websocket->toUser($users = [$userA, $userB]);
        $this->assertTrue(in_array(2, $websocket->getTo()));
        $this->assertTrue(in_array(3, $websocket->getTo()));
    }

    public function testGetUserId()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('getRooms')
             ->with($sender = 1)
             ->once()
             ->andReturn(['uid_1']);

        $websocket = $this->getWebsocket($room)->setSender($sender);
        $this->assertEquals($sender, $websocket->getUserId());
    }

    public function testLogout()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('getRooms')
             ->with($sender = 1)
             ->once()
             ->andReturn(['uid_1']);
        $room->shouldReceive('delete')
             ->with($sender, $name = ['uid_1'])
             ->once();

        $websocket = $this->getWebsocket($room)->setSender($sender);
        $websocket->logout();
    }

    public function testIsUserIdOnline()
    {
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('getClients')
             ->with('uid_1')
             ->once()
             ->andReturn([1]);

        $websocket = $this->getWebsocket($room);
        $this->assertTrue($websocket->isUserIdOnline(1));
    }

    public function testReset()
    {
        $websocket = $this->getWebsocket();
        $websocket->setSender(1)
                  ->broadcast()
                  ->to('foo');

        $websocket->reset(true);

        $this->assertNull($websocket->getSender());
        $this->assertFalse($websocket->getIsBroadcast());
        $this->assertSame([], $websocket->getTo());
    }

    public function testPipeline()
    {
        App::shouldReceive('call')
           ->once()
           ->andReturnSelf();

        $request = m::mock(Request::class);
        $middleware = ['foo', 'bar'];
        $pipeline = m::mock(Pipeline::class);
        $pipeline->shouldReceive('send')
                 ->with($request)
                 ->once()
                 ->andReturnSelf();
        $pipeline->shouldReceive('through')
                 ->with($middleware)
                 ->once()
                 ->andReturnSelf();
        $pipeline->shouldReceive('then')
                 ->once()
                 ->andReturn($request);

        $websocket = $this->getWebsocket(null, $pipeline);
        $websocket->middleware($middleware);
        $websocket->on('connect', function () {
            return 'connect';
        });

        $websocket->call('connect', $request);
    }

    public function testSetContainer()
    {
        $websocket = $this->getWebsocket();
        $container = new Container;
        $websocket->setContainer($container);

        $reflector = new \ReflectionProperty(Pipeline::class, 'container');
        $reflector->setAccessible(true);
        $wsContainer = $reflector->getValue($websocket->getPipeline());

        $this->assertSame($container, $wsContainer);
    }

    public function testSetPipeline()
    {
        $websocket = $this->getWebsocket();
        $pipeline = m::mock(Pipeline::class);

        $websocket->setPipeline($pipeline);

        $this->assertSame($pipeline, $websocket->getPipeline());
    }

    public function testEmit()
    {
        $sender = 1;
        $to = [1, 2, 'a', 'b', 'c'];
        $broadcast = true;
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('getClients')
             ->with(m::type('string'))
             ->times(3)
             ->andReturn([3, 4, 5]);

        $server = m::mock('server');
        $server->taskworker = false;

        App::shouldReceive('make')
           ->with(Server::class)
           ->once()
           ->andReturn($server);

        $server->shouldReceive('task')
           ->with([
               'action' => 'push',
               'data' => [
                   'sender' => $sender,
                   'fds' => [1, 2, 3, 4, 5],
                   'broadcast' => $broadcast,
                   'assigned' => true,
                   'event' => $event = 'event',
                   'message' => $data = 'data',
               ],
           ])
           ->once();

        $websocket = $this->getWebsocket($room);
        $websocket->setSender($sender)
                  ->to($to)
                  ->broadcast()
                  ->emit($event, $data);

        $this->assertSame([], $websocket->getTo());
        $this->assertFalse($websocket->getIsBroadcast());
    }

    public function testEmitInTaskWorker()
    {
        $sender = 1;
        $to = [1, 2, 'a', 'b', 'c'];
        $broadcast = true;
        $room = m::mock(RoomContract::class);
        $room->shouldReceive('getClients')
             ->with(m::type('string'))
             ->times(3)
             ->andReturn([3, 4, 5]);

        $payload = [
            'sender' => $sender,
            'fds' => [1, 2, 3, 4, 5],
            'broadcast' => $broadcast,
            'assigned' => true,
            'event' => $event = 'event',
            'message' => $data = 'data',
        ];

        $server = m::mock('server');
        $server->taskworker = true;

        $manager = m::mock(Manager::class);
        $manager->shouldReceive('pushMessage')
            ->with($server, $payload)
            ->once();

        App::shouldReceive('make')
           ->with(Server::class)
           ->once()
           ->andReturn($server);

        App::shouldReceive('make')
            ->with(Manager::class)
            ->once()
            ->andReturn($manager);

        $websocket = $this->getWebsocket($room);
        $websocket->setSender($sender)
                  ->to($to)
                  ->broadcast()
                  ->emit($event, $data);

        $this->assertSame([], $websocket->getTo());
        $this->assertFalse($websocket->getIsBroadcast());
    }

    public function testClose()
    {
        $fd = 1;

        App::shouldReceive('make')
           ->with(Server::class)
           ->once()
           ->andReturnSelf();

        App::shouldReceive('close')
           ->with($fd)
           ->once();

        $websocket = $this->getWebsocket();
        $websocket->close($fd);
    }

    protected function getWebsocket(RoomContract $room = null, $pipeline = null)
    {
        $room = $room ?: m::mock(RoomContract::class);
        $pipeline = $pipeline ?: m::mock(Pipeline::class);

        Config::shouldReceive('get')
              ->once()
              ->andReturn([]);

        return new Websocket($room, $pipeline);
    }
}

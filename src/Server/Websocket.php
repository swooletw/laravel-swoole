<?php

namespace SwooleTW\Http\Server;

use SwooleTW\Http\Server\Room\RoomContract;

class Websocket
{
    protected $isBroadcast;

    protected $sender;

    protected $to = [];

    /**
     * Room.
     *
     * @var SwooleTW\Http\Server\Room\RoomContract
     */
    protected $room;

    // https://gist.github.com/alexpchin/3f257d0bb813e2c8c476
    // https://github.com/socketio/socket.io/blob/master/docs/emit.md
    public function __construct(RoomContract $room)
    {
        $this->room = $room;
    }

    public function broadcast()
    {
        //
    }

    // room, fd | array
    public function to()
    {
        //
    }

    public function join(string $room)
    {
        //
    }

    public function leave()
    {
        //
    }

    public function on(string $event, callable $callback)
    {
        //
    }

    public function emit(string $event, $data)
    {
        //
    }

    public function in(string $room)
    {
        //
    }
}

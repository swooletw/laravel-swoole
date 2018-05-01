<?php

namespace SwooleTW\Http\Websocket;

use InvalidArgumentException;
use Illuminate\Support\Facades\App;
use SwooleTW\Http\Websocket\Rooms\RoomContract;

class Websocket
{
    const PUSH_ACTION = 'push';

    const EVENT_CONNECT = 'connect';

    /**
     * Determine if to broadcast.
     *
     * @var boolean
     */
    protected $isBroadcast = false;

    /**
     * Scoket sender's fd.
     *
     * @var integer
     */
    protected $sender;

    /**
     * Recepient's fd or room name.
     *
     * @var array
     */
    protected $to = [];

    /**
     * Websocket event callbacks.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Room adapter.
     *
     * @var SwooleTW\Http\Websocket\Rooms\RoomContract
     */
    protected $room;

    /**
     * Websocket constructor.
     */
    public function __construct(RoomContract $room)
    {
        $this->room = $room;
    }

    /**
     * Set broadcast to true.
     */
    public function broadcast()
    {
        $this->isBroadcast = true;

        return $this;
    }

    /**
     * Set a recepient's fd or a room name.
     *
     * @param integer, string
     */
    public function to($value)
    {
        $this->toAll([$value]);

        return $this;
    }

    /**
     * Set multiple recepients' fd or room names.
     *
     * @param array (fds or rooms)
     */
    public function toAll(array $values)
    {
        foreach ($values as $value) {
            if (! in_array($value, $this->to)) {
                $this->to[] = $value;
            }
        }

        return $this;
    }

    /**
     * Join sender to a room.
     *
     * @param string
     */
    public function join(string $room)
    {
        $this->room->add($this->sender, $room);

        return $this;
    }

    /**
     * Join sender to multiple rooms.
     *
     * @param array
     */
    public function joinAll(array $rooms)
    {
        $this->room->addAll($this->sender, $rooms);

        return $this;
    }

    /**
     * Make sender leave a room.
     *
     * @param string
     */
    public function leave(string $room)
    {
        $this->room->delete($this->sender, $room);

        return $this;
    }

    /**
     * Make sender leave multiple rooms.
     *
     * @param array
     */
    public function leaveAll(array $rooms = [])
    {
        $this->room->deleteAll($this->sender, $rooms);

        return $this;
    }

    /**
     * Emit data and reset some status.
     *
     * @param string
     * @param mixed
     */
    public function emit(string $event, $data)
    {
        $fds = $this->getFds();
        $assigned = ! empty($this->to);

        // if no fds are found, but rooms are assigned
        // that means trying to emit to a non-existing room
        // skip it directly instead of pushing to a task queue
        if (empty($fds) && $assigned) {
            return false;
        }

        $result = app('swoole.server')->task([
            'action' => static::PUSH_ACTION,
            'data' => [
                'sender' => $this->sender,
                'fds' => $fds,
                'broadcast' => $this->isBroadcast,
                'assigned' => $assigned,
                'event' => $event,
                'message' => $data
            ]
        ]);

        $this->reset();

        return $result === false ? false : true;
    }

    /**
     * An alias of `join` function.
     *
     * @param string
     */
    public function in(string $room)
    {
        $this->join($room);

        return $this;
    }

    /**
     * Register an event name with a closure binding.
     *
     * @param string
     * @param callback
     */
    public function on(string $event, $callback)
    {
        if (! is_string($callback) && ! is_callable($callback)) {
            throw new InvalidArgumentException(
                'Invalid websocket callback. Must be a string or callable.'
            );
        }

        $this->callbacks[$event] = $callback;

        return $this;
    }

    /**
     * Check if this event name exists.
     *
     * @param string
     */
    public function eventExists(string $event)
    {
        return array_key_exists($event, $this->callbacks);
    }

    /**
     * Execute callback function by its event name.
     *
     * @param string
     * @param mixed
     */
    public function call(string $event, $data = null)
    {
        if (! $this->eventExists($event)) {
            return null;
        }

        // inject request param while on connect event
        $dataKey = $event === static::EVENT_CONNECT ? 'request' : 'data';

        return App::call($this->callbacks[$event], [
            'websocket' => $this,
            $dataKey => $data
        ]);
    }

    /**
     * Close current connection.
     *
     * @param integer
     */
    public function close(int $fd = null)
    {
        return app('swoole.server')->close($fd ?: $this->sender);
    }

    /**
     * Set sender fd.
     *
     * @param integer
     */
    public function setSender(int $fd)
    {
        $this->sender = $fd;

        return $this;
    }

    /**
     * Get current sender fd.
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Get broadcast status value.
     */
    public function getIsBroadcast()
    {
        return $this->isBroadcast;
    }

    /**
     * Get push destinations (fd or room name).
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get all fds we're going to push data to.
     */
    protected function getFds()
    {
        $fds = array_filter($this->to, function ($value) {
            return is_integer($value);
        });
        $rooms = array_diff($this->to, $fds);

        foreach ($rooms as $room) {
            $fds = array_merge($fds, $this->room->getClients($room));
        }

        return array_values(array_unique($fds));
    }

    /**
     * Reset some data status.
     */
    public function reset($force = false)
    {
        $this->isBroadcast = false;
        $this->to = [];

        if ($force) {
            $this->sender = null;
        }

        return $this;
    }
}

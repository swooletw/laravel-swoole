<?php

namespace SwooleTW\Http\Websocket\Rooms;

interface RoomContract
{
    /**
     * Do some init stuffs before workers started.
     */
    public function prepare();

    /**
     * Add multiple socket fds to a room.
     *
     * @int fd
     * @string|array rooms
     */
    public function add(int $fd, $rooms);

    /**
     * Delete multiple socket fds from a room.
     *
     * @int fd
     * @string|array rooms
     */
    public function delete(int $fd, $rooms);

    /**
     * Get all sockets by a room key.
     *
     * @string room
     */
    public function getClients(string $room);

    /**
     * Get all rooms by a fd.
     *
     * @int fd
     */
    public function getRooms(int $fd);
}

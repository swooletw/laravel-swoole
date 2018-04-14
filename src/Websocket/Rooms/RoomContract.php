<?php

namespace SwooleTW\Http\Websocket\Rooms;

interface RoomContract
{
    /**
     * Do some init stuffs before workers started.
     */
    public function prepare();

    /**
     * Add a socket to a room.
     *
     * @int fd
     * @string room
     */
    public function add(int $fd, string $room);

    /**
     * Add a socket to multiple rooms.
     *
     * @int fd
     * @array room
     */
    public function addAll(int $fd, array $rooms);

    /**
     * Delete a socket from a room.
     *
     * @int fd
     * @string room
     */
    public function delete(int $fd, string $room);

    /**
     * Delete a socket from all rooms.
     *
     * @int fd
     * @string room
     */
    public function deleteAll(int $fd);

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

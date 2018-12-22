<?php

namespace SwooleTW\Http\Websocket\SocketIO;

/**
 * Class Packet
 */
class Packet
{
    /**
     * Socket.io packet type `open`.
     */
    const OPEN = 0;

    /**
     * Socket.io packet type `close`.
     */
    const CLOSE = 1;

    /**
     * Socket.io packet type `ping`.
     */
    const PING = 2;

    /**
     * Socket.io packet type `pong`.
     */
    const PONG = 3;

    /**
     * Socket.io packet type `message`.
     */
    const MESSAGE = 4;

    /**
     * Socket.io packet type 'upgrade'
     */
    const UPGRADE = 5;

    /**
     * Socket.io packet type `noop`.
     */
    const NOOP = 6;

    /**
     * Engine.io packet type `connect`.
     */
    const CONNECT = 0;

    /**
     * Engine.io packet type `disconnect`.
     */
    const DISCONNECT = 1;

    /**
     * Engine.io packet type `event`.
     */
    const EVENT = 2;

    /**
     * Engine.io packet type `ack`.
     */
    const ACK = 3;

    /**
     * Engine.io packet type `error`.
     */
    const ERROR = 4;

    /**
     * Engine.io packet type 'binary event'
     */
    const BINARY_EVENT = 5;

    /**
     * Engine.io packet type `binary ack`. For acks with binary arguments.
     */
    const BINARY_ACK = 6;

    /**
     * Socket.io packet types.
     */
    public static $socketTypes = [
        0 => 'OPEN',
        1 => 'CLOSE',
        2 => 'PING',
        3 => 'PONG',
        4 => 'MESSAGE',
        5 => 'UPGRADE',
        6 => 'NOOP',
    ];

    /**
     * Engine.io packet types.
     */
    public static $engineTypes = [
        0 => 'CONNECT',
        1 => 'DISCONNECT',
        2 => 'EVENT',
        3 => 'ACK',
        4 => 'ERROR',
        5 => 'BINARY_EVENT',
        6 => 'BINARY_ACK',
    ];

    /**
     * Get socket packet type of a raw payload.
     *
     * @param string $packet
     *
     * @return int|null
     */
    public static function getSocketType(string $packet)
    {
        $type = $packet[0] ?? null;

        if (! array_key_exists($type, static::$socketTypes)) {
            return null;
        }

        return (int) $type;
    }

    /**
     * Get data packet from a raw payload.
     *
     * @param string $packet
     *
     * @return array|null
     */
    public static function getPayload(string $packet)
    {
        $packet = trim($packet);
        $start = strpos($packet, '[');

        if ($start === false || substr($packet, -1) !== ']') {
            return null;
        }

        $data = substr($packet, $start, strlen($packet) - $start);
        $data = json_decode($data, true);

        if (is_null($data)) {
            return null;
        }

        return [
            'event' => $data[0],
            'data' => $data[1] ?? null,
        ];
    }

    /**
     * Return if a socket packet belongs to specific type.
     *
     * @param $packet
     * @param string $typeName
     *
     * @return bool
     */
    public static function isSocketType($packet, string $typeName)
    {
        $type = array_search(strtoupper($typeName), static::$socketTypes);

        if ($type === false) {
            return false;
        }

        return static::getSocketType($packet) === $type;
    }
}
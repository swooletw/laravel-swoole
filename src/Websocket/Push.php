<?php

namespace SwooleTW\Http\Websocket;

use Illuminate\Support\Arr;

/**
 * Class Push
 */
class Push
{
    /**
     * @var int
     */
    protected $opcode;

    /**
     * @var int
     */
    protected $sender;

    /**
     * @var array
     */
    protected $descriptors;

    /**
     * @var bool
     */
    protected $broadcast;

    /**
     * @var bool
     */
    protected $assigned;

    /**
     * @var string
     */
    protected $event;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * Push constructor.
     *
     * @param int $opcode
     * @param int $sender
     * @param array $descriptors
     * @param bool $broadcast
     * @param bool $assigned
     * @param string $event
     * @param string|null $message
     */
    protected function __construct(
        int $opcode,
        int $sender,
        array $descriptors,
        bool $broadcast,
        bool $assigned,
        string $event,
        string $message = null
    )
    {
        $this->opcode = $opcode;
        $this->sender = $sender;
        $this->descriptors = $descriptors;
        $this->broadcast = $broadcast;
        $this->assigned = $assigned;
        $this->event = $event;
        $this->message = $message;
    }

    /**
     * Static constructor
     *
     * @param array $data
     *
     * @return \SwooleTW\Http\Websocket\Push
     */
    public static function new(array $data)
    {
        $opcode = Arr::get($data, 'opcode', 1);
        $sender = Arr::get($data, 'sender', 0);
        $descriptors = Arr::get($data, 'fds', []);
        $broadcast = Arr::get($data, 'broadcast', false);
        $assigned = Arr::get($data, 'assigned', false);
        $event = Arr::get($data, 'event', '');
        $message = Arr::get($data, 'message', null);

        return new static($opcode, $sender, $descriptors, $broadcast, $assigned, $event, $message);
    }

    /**
     * @return int
     */
    public function getOpcode(): int
    {
        return $this->opcode;
    }

    /**
     * @return int
     */
    public function getSender(): int
    {
        return $this->sender;
    }

    /**
     * @return array
     */
    public function getDescriptors(): array
    {
        return $this->descriptors;
    }

    /**
     * @param int $descriptor
     */
    public function addDescriptor($descriptor): void
    {
        $this->descriptors[] = $descriptor;
    }

    /**
     * @param array $descriptors
     */
    public function mergeDescriptor(array $descriptors): void
    {
        $this->descriptors[] = array_merge($this->descriptors, $descriptors);
    }

    /**
     * @param int $descriptor
     *
     * @return bool
     */
    public function hasDescriptor(int $descriptor): bool
    {
        return \in_array($descriptor, $this->descriptors, true);
    }

    /**
     * @return bool
     */
    public function hasOwnDescriptor(): bool
    {
        return $this->hasDescriptor($this->sender);
    }

    /**
     * @return bool
     */
    public function isBroadcast(): bool
    {
        return $this->broadcast;
    }

    /**
     * @return bool
     */
    public function isAssigned(): bool
    {
        return $this->assigned;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isBroadcastToAllDescriptors(): bool
    {
        return $this->isBroadcast() && ! $this->isAssigned() && count($this->descriptors) > 0;
    }

    /**
     * @param int $descriptor
     *
     * @return bool
     */
    public function isBroadcastToDescriptor(int $descriptor): bool
    {
        return $this->isBroadcast() && $this->getSender() === $descriptor;
    }
}
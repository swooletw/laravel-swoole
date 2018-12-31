<?php

namespace SwooleTW\Http\HotReload;

use Illuminate\Support\Arr;
use Carbon\Carbon;

/**
 * Class FSEvent
 */
class FSEvent
{
    /**
     * Event - Created.
     *
     * @var string
     */
    public const Created = 'Created';

    /**
     * Event - Updated.
     *
     * @var string
     */
    public const Updated = 'Updated';

    /**
     * Event - Removed.
     *
     * @var string
     */
    public const Removed = 'Removed';

    /**
     * Event - Renamed.
     *
     * @var string
     */
    public const Renamed = 'Renamed';

    /**
     * Event - OwnerModified.
     *
     * @var string
     */
    public const OwnerModified = 'OwnerModified';

    /**
     * Event - AttributeModified.
     *
     * @var string
     */
    public const AttributeModified = 'AttributeModified';

    /**
     * Event - MovedFrom.
     *
     * @var string
     */
    public const MovedFrom = 'MovedFrom';

    /**
     * Event - MovedTo.
     *
     * @var string
     */
    public const MovedTo = 'MovedTo';

    /**
     * Possible event types.
     *
     * @var array
     */
    protected static $possibleTypes = [
        self::Created, self::Updated, self::Removed, self::Renamed,
        self::OwnerModified, self::AttributeModified, self::MovedFrom, self::MovedTo,
    ];

    /**
     * When event fired.
     *
     * @var \Carbon\Carbon
     */
    protected $when;

    /**
     * Directory or file path.
     *
     * @var string
     */
    protected $path;

    /**
     * Event types.
     *
     * @var array
     */
    protected $types;

    /**
     * Event constructor.
     *
     * @param \Carbon\Carbon $when
     * @param string $path
     * @param array $types
     */
    public function __construct(Carbon $when, string $path, array $types)
    {
        $this->when = $when;
        $this->path = $path;
        $this->types = $types;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getWhen(): Carbon
    {
        return $this->when;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Arr::first($this->types);
    }

    /**
     * Checks if event types has needed type(s).
     *
     * @param string ...$types
     *
     * @return bool
     */
    public function isType(string ...$types): bool
    {
        return count(array_intersect($this->types, $types)) > 0;
    }

    /**
     * Get possible event types.
     *
     * @return array
     */
    public static function getPossibleTypes(): array
    {
        return self::$possibleTypes;
    }
}
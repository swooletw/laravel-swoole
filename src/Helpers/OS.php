<?php

namespace SwooleTW\Http\Helpers;


use Illuminate\Support\Str;

/**
 * Class OS
 */
final class OS
{
    /**
     * Mac OS
     *
     * @const string
     */
    public const MAC_OS = 'dar';

    /**
     * Linux
     *
     * @const string
     */
    public const LINUX = 'linux';

    /**
     * Linux
     *
     * @const string
     */
    public const WIN = 'win';

    /**
     * Returns true if current OS in types
     *
     * @param string ...$types
     *
     * @return bool
     */
    public static function is(string ...$types): bool
    {
        return \in_array(static::current(), $types, true);
    }

    /**
     * Current OS
     *
     * @return string
     */
    public static function current(): string
    {
        return Str::lower(PHP_OS);
    }
}
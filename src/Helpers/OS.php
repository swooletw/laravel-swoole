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
    public const LINUX = 'lin';

    /**
     * Linux
     *
     * @const string
     */
    public const WIN = 'win';

    /**
     * Cygwin
     *
     * @const string
     */
    public const CYGWIN = 'cyg';

    /**
     * Returns true if current OS in types
     *
     * @param string ...$types
     *
     * @return bool
     */
    public static function is(string ...$types): bool
    {
        return Str::contains(static::current(), $types);
    }

    /**
     * Current OS
     *
     * @return string
     */
    public static function current(): string
    {
        return Str::substr(Str::lower(PHP_OS), 0, 3);
    }
}

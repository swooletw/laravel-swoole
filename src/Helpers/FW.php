<?php

namespace SwooleTW\Http\Helpers;


use Illuminate\Support\Str;

/**
 * Class FW
 */
final class FW
{
    /**
     * Laravel
     *
     * @const string
     */
    public const LARAVEL = 'laravel';

    /**
     * Lumen
     *
     * @const string
     */
    public const LUMEN = 'lumen';

    /**
     * Returns true if current Framework in types
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
     * Current Framework
     *
     * @return string
     */
    public static function current(): string
    {
        return Str::lower(PHP_OS);
    }
}
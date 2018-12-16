<?php

namespace SwooleTW\Http\Helpers;


use Illuminate\Support\Arr;
use LogicException;

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
     * Version regular expression
     *
     * @const string
     */
    private const VERSION_EXPRESSION = '/\s*(?:\d+\.?)+/i';

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
     * Returns application version
     *
     * @return string
     */
    public static function version(): string
    {
        if (static::is(static::LARAVEL)) {
            return constant('Illuminate\Foundation\Application::VERSION');
        }

        /** @var \Laravel\Lumen\Application $app */
        $app = call_user_func('Laravel\Lumen\Application::getInstance');

        if (preg_match(static::VERSION_EXPRESSION, $app->version(), $result)) {
            return Arr::first($result);
        }

        throw new LogicException('No any version found.');
    }

    /**
     * Current Framework
     *
     * @return string
     */
    public static function current(): string
    {
        return class_exists('Illuminate\Foundation\Application') ? static::LARAVEL : static::LUMEN;
    }
}
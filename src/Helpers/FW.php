<?php

namespace SwooleTW\Http\Helpers;

use LogicException;
use Illuminate\Support\Arr;

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
    public const VERSION_FULL = '/\s*(?:\d+\.?)+/i';

    /**
     * Version without bugfix regular expression
     *
     * @const string
     */
    public const VERSION_WITHOUT_BUG_FIX = '/\s*(?:\d+\.*?){2}/i';

    /**
     * Returns true if current Framework in types
     *
     * @param string ...$types
     *
     * @return bool
     */
    public static function is(string ...$types): bool
    {
        return in_array(static::current(), $types, true);
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

    /**
     * Returns application version
     *
     * @param string $expression
     *
     * @return string
     */
    public static function version(string $expression = self::VERSION_WITHOUT_BUG_FIX): string
    {
        if (static::is(static::LARAVEL)) {
            return static::extractVersion(constant('Illuminate\Foundation\Application::VERSION'), $expression);
        }

        /** @var \Laravel\Lumen\Application $app */
        $app = call_user_func('Laravel\Lumen\Application::getInstance');

        if ($version = static::extractVersion($app->version(), $expression)) {
            return $version;
        }

        throw new LogicException('No any version found.');
    }

    /**
     * Extracts lumen version from $app->version()
     *
     * @param string $version
     * @param string $expression
     *
     * @return string|null
     */
    protected static function extractVersion(string $version, string $expression): ?string
    {
        if (preg_match($expression, $version, $result)) {
            return Arr::first($result);
        }

        return null;
    }
}
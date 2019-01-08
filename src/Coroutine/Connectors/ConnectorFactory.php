<?php

namespace SwooleTW\Http\Coroutine\Connectors;

use Illuminate\Support\Arr;
use SwooleTW\Http\Helpers\FW;

/**
 * Class ConnectorFactory
 */
class ConnectorFactory
{
    /**
     * Version with breaking changes
     *
     * @const string
     */
    public const CHANGE_VERSION = '5.6';

    /**
     * Swoole connector class
     *
     * @const string
     */
    public const CONNECTOR_CLASS = 'SwooleTW\Http\Coroutine\Connectors\MySqlConnector';

    /**
     * Swoole connector path
     *
     * @const string
     */
    public const CONNECTOR_CLASS_PATH = __DIR__ . '/MySqlConnector.php';

    /**
     * @param string $version
     *
     * @return \SwooleTW\Http\Coroutine\Connectors\MySqlConnector
     */
    public static function make(string $version): MySqlConnector
    {
        $isMatch = static::isFileVersionMatch($version);
        $class = static::copy(static::stub($version), ! $isMatch);

        return new $class;
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public static function stub(string $version): string
    {
        return static::hasBreakingChanges($version)
            ? __DIR__ . '/../../../stubs/5.6/MySqlConnector.stub'
            : __DIR__ . '/../../../stubs/5.5/MySqlConnector.stub';
    }

    /**
     * @param string $stub
     * @param bool $rewrite
     *
     * @return string
     */
    public static function copy(string $stub, bool $rewrite = false): string
    {
        if (! file_exists(static::CONNECTOR_CLASS_PATH) || $rewrite) {
            copy($stub, static::CONNECTOR_CLASS_PATH);
        }

        return static::CONNECTOR_CLASS;
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    protected static function isFileVersionMatch(string $version): bool
    {
        try {
            $fileVersion = null;
            if (class_exists(self::CONNECTOR_CLASS)) {
                $ref = new \ReflectionClass(self::CONNECTOR_CLASS);
                if (preg_match(FW::VERSION_WITHOUT_BUG_FIX, $ref->getDocComment(), $result)) {
                    $fileVersion = Arr::first($result);
                }
            }

            return version_compare($fileVersion, $version, '>=');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    protected static function hasBreakingChanges(string $version): bool
    {
        return version_compare($version, self::CHANGE_VERSION, '>=');
    }
}

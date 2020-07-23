<?php

namespace SwooleTW\Http\Helpers;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class Dumper
{
    protected static $cloner;

    public static function dump(...$args)
    {
        if (! static::$cloner instanceOf VarCloner) {
            static::$cloner = new VarCloner;
        }

        $dumper = static::getDumper();

        foreach ($args as $arg) {
            $dumper->dump(
                static::$cloner->cloneVar($arg)
            );
        }

        return true;
    }

    public static function getDumper()
    {
        $dumper = defined('IN_PHPUNIT') || ! config('swoole_http.ob_output')
            ? CliDumper::class
            : HtmlDumper::class;

        return new $dumper;
    }
}

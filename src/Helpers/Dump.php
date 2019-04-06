<?php


namespace SwooleTW\Http\Helpers;


class Dump
{
    // define the dumper class
    protected static $dumper_class = 'Symfony\Component\VarDumper\Dumper\HtmlDumper';
    protected static $cloner_class = 'Symfony\Component\VarDumper\Cloner\VarCloner';

    public static function dd(...$args) {

        // only works when these classes are existing
        // otherwise return false
        if (!class_exists(static::$dumper_class) || !class_exists(static::$cloner_class)) {
            return false;
        }

        // init the dumper and cloner
        $dumper = new static::$dumper_class();
        $cloner = new static::$cloner_class();

        // dump each var in the args
        foreach ($args as $arg) {
            if (defined('IN_PHPUNIT')) {
                continue;
            }
            $dumper->dump($cloner->cloneVar($arg));
        }

        return true;
    }
}
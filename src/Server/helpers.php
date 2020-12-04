<?php

/**
 * This is only for `function not exists` in config/swoole_http.php.
 */
if (! function_exists('swoole_cpu_num')) {
    function swoole_cpu_num(): int
    {
        return 1;
    }
}

/**
 * This is only for `function not exists` in config/swoole_http.php.
 */
if (! defined('SWOOLE_SOCK_TCP')) {
    define('SWOOLE_SOCK_TCP', 1);
}

if (! defined('SWOOLE_PROCESS')) {
    define('SWOOLE_PROCESS', 2);
}

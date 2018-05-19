<?php

/**
 * This is only for `function not exists` in config/swoole_http.php.
 */
if (! function_exists('swoole_cpu_num')) {
    function swoole_cpu_num()
    {
        return;
    }
}

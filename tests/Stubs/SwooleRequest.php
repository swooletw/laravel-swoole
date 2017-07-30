<?php

/*
 * This file is part of the huang-yi/laravel-swoole-http package.
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Http\Tests\Stubs;

use Swoole\Http\Request;

class SwooleRequest extends Request
{
    public $get = [];
    public $post = [];
    public $header = [];
    public $server = [];
    public $cookie = [];
    public $files = [];
    public $fd = 1;

    /**
     * 获取非urlencode-form表单的POST原始数据
     * @return string
     */
    function rawContent()
    {
        return 'foo=bar';
    }
}

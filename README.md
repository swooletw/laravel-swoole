# Laravel-Swoole

![php-badge](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)
[![packagist-badge](https://img.shields.io/packagist/v/swooletw/laravel-swoole.svg)](https://packagist.org/packages/swooletw/laravel-swoole)
[![Total Downloads](https://poser.pugx.org/swooletw/laravel-swoole/downloads)](https://packagist.org/packages/swooletw/laravel-swoole)
[![travis-badge](https://api.travis-ci.org/swooletw/laravel-swoole.svg?branch=master)](https://travis-ci.org/swooletw/laravel-swoole)

This package provides a high performance HTTP server to speed up your laravel/lumen application based on [Swoole](http://www.swoole.com/).

## Version Compatibility

| PHP     | Laravel | Lumen | Swoole  |
|:-------:|:-------:|:-----:|:-------:|
| >=7.1 | ~5.1    | ~5.1  | >=1.9.3 |

## Installation

Require this package with composer by using the following command:

```
$ composer require swooletw/laravel-swoole
```

> This package relies on Swoole. Please make sure your machine has been installed the Swoole extension. Using this command to install quickly: `pecl install swoole`. Visit the [official website](https://wiki.swoole.com/wiki/page/6.html) for more information.

Then, add the service provider:

If you are using Laravel, add the service provider to the providers array in `config/app.php`:

```php
[
    'providers' => [
        SwooleTW\Http\LaravelServiceProvider::class,
    ],
]
```

If you are using Lumen, append the following code to `bootstrap/app.php`:

```php
$app->register(SwooleTW\Http\LumenServiceProvider::class);
```

## Configuration

If you want to change the default configurations, please run the following command to generate configuration files `swoole_http.php` and `swoole_websocket.php` in directory `config/`:

```
$ php artisan vendor:publish
```

### swoole_http.php

`server.host`: The swoole_http_server host.

`server.port`: The swoole_http_server port.

`server.options`: The configurations for `Swoole\Server`. To get more information about swoole server, please read [the official documentation](https://wiki.swoole.com/wiki/page/274.html).

For example, if you want to set the 'max_request':

```php
[
    'server' => [
        'options' => [
            'max_request' => 1000,
        ],
    ]
]
```

`websocket`: The switch to turn on websocket feature.

```php
[
    'websocket' => 'false',
]
```

`providers`: The service providers you want to reset on every request. It will re-register and reboot those service providers before requesting every time.

```php
[
    'providers' => [
        App\Providers\AuthServiceProvider::class,
    ]
]
```

`tables`: You can customize your swoole tables here. See [here](https://wiki.swoole.com/wiki/page/p-table.html) for more detailed information.

```php
[
    'tables' => [
        'table_name' => [
            'size' => 1024,
            'columns' => [
                ['name' => 'column_name', 'type' => Table::TYPE_STRING, 'size' => 1024],
            ]
        ],
    ]
]
```

### swoole_websocket.php

`handler`: Websocket handler for onOpen and onClose callback function. Replace this handler before you start it.

```php
[
    // handler must implement \SwooleTW\Http\Websocket\HandlerContract
    handler' => SwooleTW\Http\Websocket\WebsocketHandler::class,
]
```

`handlers`: Websocket handlers mapping for onMessage callback. Message frames from clients will be parsed by formatter first (`SwooleTW\Http\Websocket\Formatters\DefaultFormatter`). It will dispatch the logic to matched event name.

> By default raw frame data will be expected as: `{"event":"event_name","data":"data_content"}`. If no events name matched, it will call your `onMessage` function of `WebsocketHandler`

```php
[
    'handlers' => [
        'event_name' => 'App\Handlers\ExampleHandler@function',
    ],
]
```

`default`: Default websocket driver. It only supports `table` at this momnet.

```php
[
    'default' => 'table',
]
```

`formatter`: Default message formatter, replace it if you want to customize your websocket payload.

```php
[
    // formatter must implement \SwooleTW\Http\Websocket\Formatters\FormatterContract
    'formatter' => SwooleTW\Http\Websocket\Formatters\DefaultFormatter::class,
]
```

## Commands

> The swoole_http_server can only run in cli environment, and this package provides convenient artisan commands to manage it.
> By default, you can visit your site at http://127.0.0.1:1215

> `php artisan swoole:http {start|stop|restart|reload|infos|publish}`

| Command | Description |
| --------- | --------- |
| `start` | Start Laravel Swoole, list the processes by *ps aux&#124;grep swoole* |
| `stop` | Stop Laravel Swoole |
| `restart` | Restart Laravel Swoole |
| `reload` | Reload all worker process(Contain your business & Laravel/Lumen codes), exclude master/manger process |
| `infos` | Show PHP and Swoole basic miscs infos(including PHP version, Swoole version, Laravel version, server status and PID) |
| `publish` | Publish configuration files `swoole_http.php` and `swoole_websocket.php` to `config` folder of your project |

Now, you can run the following command to start the **swoole_http_server**.

```
$ php artisan swoole:http start
```

## Get related instances in your project

* Swoole\Http\Server: By `SwooleTW\Http\Server\Facades\Server` or `app('swoole.server')`

* SwooleTW\Http\Server\Table: By `SwooleTW\Http\Server\Facades\Table` or `app('swoole.table')`

```php
// you can get your customized swoole tables like this
Table::get('table_name');

// get all defined tables
Table::getAll();
```

* SwooleTW\Http\Websocket\Websocket: By `SwooleTW\Http\Websocket\Facades\Websocket` or `app('swoole.websocket')`

> The usage of websocket refers to `socket.io`, so they look a liitle bit similar.

```php
// sending to sender-client only
Websocket::emit('message', 'this is a test');

// sending to all clients except sender
Websocket::broadcast()->emit('message', 'this is a test');

// sending to all clients in 'game' room(channel) except sender
Websocket::broadcast()->to('game')->emit('message', 'nice game');

// sending to all clients in 'game' including sender client
Websocket::to('game')->emit('message', 'enjoy the game');

// sending to individual socketid 1
Websocket::broadcast()->to(1).emit('message', 'for your eyes only');

// join to subscribe the socket to a given channel (server-side):
Websocket::join('some room');

// leave to unsubscribe the socket to a given channel (server-side)
Websocket::leave('some room');
```

## Nginx Configuration

> The support of swoole_http_server for Http is not complete. So, you should configure the domains via nginx proxy in your production environment.

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /path/to/laravel/public;
    index index.php;

    location = /index.php {
        # Ensure that there is no such file named "not_exists"
        # in your "public" directory.
        try_files /not_exists @swoole;
    }

    location / {
        try_files $uri $uri/ @swoole;
    }

    location @swoole {
        set $suffix "";

        if ($uri = /index.php) {
            set $suffix "/";
        }

        proxy_set_header Host $host;
        proxy_set_header SERVER_PORT $server_port;
        proxy_set_header REMOTE_ADDR $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;

        # IF https
        # proxy_set_header HTTPS "on";

        proxy_pass http://127.0.0.1:1215$suffix;
    }
}
```

## Performance Reference

Test with clean Lumen 5.5, using MacBook Air 13, 2015.
Benchmarking Tool: [wrk](https://github.com/wg/wrk)

```
wrk -t4 -c100 http://your.app
```

### Nginx with FPM

```
Running 10s test @ http://lumen.app:9999
  4 threads and 100 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     1.14s   191.03ms   1.40s    90.31%
    Req/Sec    22.65     10.65    50.00     65.31%
  815 requests in 10.07s, 223.65KB read
Requests/sec:     80.93
Transfer/sec:     22.21KB
```

### Swoole HTTP Server

```
Running 10s test @ http://127.0.0.1:1215
  4 threads and 100 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency    11.58ms    4.74ms  68.73ms   81.63%
    Req/Sec     2.19k   357.43     2.90k    69.50%
  87879 requests in 10.08s, 15.67MB read
Requests/sec:   8717.00
Transfer/sec:      1.55MB
```

## Lifecycle

> This is a rough description of the lifecycle in Swoole and Laravel. It may be helpful for you to know more about how this pakcage launches your Laravel application with swoole server.

* Initialize
    * Create swoole tables.
    * Prepare websocket.
    * Create swoole server
    * Configure swoole server
    * Set swoole server listeners
    
* onStart()
    * Create pid file.

* onWorkerStart()
    * Clear APC and Opcache.
    * Set framework type and base path.
    * Load `/bootstrap/app.php`.
    * Bootstrap framework.
    * Bind swoole server, swoole table, room and websocket instances

* onRequest()
    * Reset designated service providers
    * Convert swoole request to illuminate request.
    * Make `Illuminate\Contracts\Http\Kernel` (Laravel).
    * Handle/dispatch HTTP request.
    * Terminate request in Laravel, or reset middleware in Lumen.
    * Convert illuminate response to swoole response.
    * Send response to client.

* onOpen()
    * Convert swoole request to illuminate request.
    * Dispatch request to related handler.

* onMessage()
    * Set sender fd to websocket instance.
    * Tansform payload data via formatter.
    * Dispatch payload to related handler.

* onClose()
    * Dispatch client's fd to related handler.
    * Remove disconnected fd from all the rooms. 
    
* onShutdown()
    * Remove pid file.
    
## Notices

1. Please reload or restart the swoole_http_server after released your code. Because the Laravel program will be kept in memory after the swoole_http_server started. That's why the swoole_http_server has high performance.
2. Never use `dd()`, `exit()` or `die()` function to print your debug message. It will terminate your swoole worker unexpectedly.
3. `global` and `static` variables needs to be destroyed(reset) manually.
4. Infinitely appending element into static/global variable will lead to memory leak.
```php
// Some class
class Test
{
    public static $array = [];
    public static $string = '';
}

// Controller
public function test(Request $req)
{
    // Memory leak
    Test::$array[] = $req->input('param1');
    Test::$string .= $req->input('param2');
}
```
5. You should have basic knowledge about multi-process programming and swoole. If you still write your code with traditional php concept, your app might have unexpected bugs.

## Support

Bugs and feature request are tracked on [Github](https://github.com/swooletw/laravel-swoole-http/issues).

## Credits

The original author of this package: [Huang-Yi](https://github.com/huang-yi)

## License

The Laravel-Swoole-Http package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

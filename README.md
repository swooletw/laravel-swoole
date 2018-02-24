# Laravel-Swoole

![php-badge](https://img.shields.io/badge/php-%3E%3D%205.5.9-8892BF.svg)
[![packagist-badge](https://img.shields.io/packagist/v/swooletw/laravel-swoole.svg)](https://packagist.org/packages/swooletw/laravel-swoole)
[![Total Downloads](https://poser.pugx.org/swooletw/laravel-swoole/downloads)](https://packagist.org/packages/swooletw/laravel-swoole)
[![travis-badge](https://api.travis-ci.org/swooletw/laravel-swoole.svg?branch=master)](https://travis-ci.org/swooletw/laravel-swoole)

This package provides a high performance HTTP server to speed up your laravel/lumen application based on [Swoole](http://www.swoole.com/).

## Version Compatibility

| PHP     | Laravel | Lumen | Swoole  |
|:-------:|:-------:|:-----:|:-------:|
| >=5.5.9 | ~5.1    | ~5.1  | >=1.9.3 |

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

If you want to change the default configurations, please run the following command to generate a configuration file `swoole_http.php` in directory `config/`:

```
$ php artisan vendor:publish
```

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

`providers`: The service providers you want to reset on every request. It will re-register and reboot those service providers before requesting every time.

```php
[
    'providers' => [
        App\Providers\AuthServiceProvider::class,
    ]
]
```

## Commands

> The swoole_http_server can only run in cli environment, and this package provides convenient artisan commands to manage it.
> By default, you can visit your site at http://127.0.0.1:1215

> `php artisan swoole:http {start|stop|restart|reload|infos|publish}`

| Command | Description |
| --------- | --------- |
| `start` | Start LaravelS, list the processes by *ps -ef&#124;grep laravels* |
| `stop` | Stop LaravelS |
| `restart` | Restart LaravelS |
| `reload` | Reload all worker process(Contain your business & Laravel/Lumen codes), exclude master/manger process |
| `infos` | Show PHP and Swoole basic miscs infos(including PHP version, Swoole version, Laravel version, server status and PID) |
| `publish` | Publish configuration file `swoole_http.php` to `config` folder of your project |

Now, you can run the following command to start the **swoole_http_server**.

```
$ php artisan swoole:http start
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

* onStart()
    * Create pid file.

* onWorkerStart()
    * Clear APC and Opcache.
    * Set framework type and base path.
    * Load `/bootstrap/app.php`.
    * Bootstrap framework.

* onRequest()
    * Convert swoole request to illuminate request.
    * Make `Illuminate\Contracts\Http\Kernel` (Laravel).
    * Handle/dispatch HTTP request.
    * Terminate request in Laravel, or reset middleware in Lumen.
    * Convert illuminate response to swoole response.
    * Send response to client.
    * Unset request and response.

* onShutdown()
    * Remove pid file.
    
## Notices

1. Please reload or restart the swoole_http_server after released your code. Because the Laravel program will be kept in memory after the swoole_http_server started. That's why the swoole_http_server has high performance.
2. Never use `dd()`, `exit()` or `die()` function to print your debug message. It will terminate your swoole worker unexpectedly.
3. You should have basic knowledge about multi-process programming and swoole. If you still write your code with traditional php concept, your app might have unexpected bugs.

## Support

Bugs and feature request are tracked on [Github](https://github.com/swooletw/laravel-swoole-http/issues).

## Credits

The original author of this package: [Huang-Yi](https://github.com/huang-yi)

## License

The Laravel-Swoole-Http package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

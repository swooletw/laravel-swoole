# Laravel-Swoole

![php-badge](https://img.shields.io/badge/php-%3E%3D%205.5.9-8892BF.svg)
[![packagist-badge](https://img.shields.io/packagist/v/swooletw/laravel-swoole.svg)](https://packagist.org/packages/swooletw/laravel-swoole)
[![Total Downloads](https://poser.pugx.org/swooletw/laravel-swoole/downloads)](https://packagist.org/packages/swooletw/laravel-swoole)

This package provides a high performance HTTP server to spped up your laravel/lumen application based on [Swoole](http://www.swoole.com/).

## Version Compatibility

| PHP     | Laravel | Lumen | Swoole  |
|:-------:|:-------:|:-----:|:-------:|
| >=5.5.9 | ~5.1    | ~5.1  | >=1.9.3 |

## Quick Start

Require this package with composer by using the following command:

```
$ composer require swooletw/laravel-swoole
```

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

Now, you can run the following command to start the **swoole_http_server**.

```
$ php artisan swoole:http start
```

By default, you can visit your site at http://127.0.0.1:1215. And you can also configure domains via nginx proxy:

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /path/to/laravel/public;
    index index.php;

    location = /index.php {
        # Ensure that there is no such file named "not_exists" in your "public" directory.
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

## Documentation

- [Full Docuementation](docs/english.md)

## Support

Bugs and feature request are tracked on [Github](https://github.com/swooletw/laravel-swoole-http/issues).

## Credits

The original author of this package: [Huang-Yi](https://github.com/huang-yi)

## License

The Laravel-Swoole-Http package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

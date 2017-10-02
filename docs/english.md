# Laravel-Swoole-Http

This package provides a high performance HTTP server which based on [Swoole](http://www.swoole.com/).

## Installation

Require this package with composer by using the following command:

```sh
$ composer require huang-yi/laravel-swoole-http
```

> This package is relied on Swoole. Please make sure your machine has been installed the Swoole extension. Using this command to install quickly: `pecl install swoole`. Visit the [official website](https://wiki.swoole.com/wiki/page/6.html) for more information.

## Service Provider

If you are using Laravel, add the service provider to the providers array in `config/app.php`:

```php
[
    'providers' => [
        HuangYi\Http\LaravelServiceProvider::class,
    ],
]
```

If you are using Lumen, add the following code to `bootstrap/app.php`:

```php
$app->register(HuangYi\Http\LumenServiceProvider::class);
```

## Configuration

If you want to change the default configurations, please run the following command to generate a configuration file `http.php` in directory `config/`:

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

## Command

> The swoole_http_server can only run in cli environment, and this package provides convenient artisan commands to manage it.

Start the swoole_http_server:

```
$ php artisan swoole:http start
```

Stop the swoole_http_server:

```
$ php artisan swoole:http stop
```

Restart the swoole_http_server:

```
$ php artisan swoole:http restart
```

Reload the swoole_http_server:

```
$ php artisan swoole:http reload
```

## Nginx Configuration

> The swoole_http_server support for Http is not complete. So, you should configure the domains via nginx proxy.

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

## Notice

You should reload or restart the swoole_http_server after released your code. Because the Laravel program will be kept in memory after the swoole_http_server started. That's why the swoole_http_server has high performance.

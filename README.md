# Laravel-Swoole

This package provides a high performance HTTP server which based on [Swoole](http://www.swoole.com/).

## Version Compatibility

| PHP     | Laravel | Lumen | Swoole  |
|:-------:|:-------:|:-----:|:-------:|
| >=5.5.9 | ~5.1    | ~5.1  | >=1.9.3 |

## Quick Start

Require this package with composer by using the following command:

```
$ composer require swooletw/laravel-swoole-http
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

## Documentation

- [English](docs/english.md)
- [简体中文](docs/chinese.md)

## Support

Bugs and feature request are tracked on [Github](https://github.com/swooletw/laravel-swoole-http/issues).

## License

The Laravel-Swoole-Http package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
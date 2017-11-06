# Laravel-Swoole-Http

这个拓展包提供了一个基于[Swoole](http://www.swoole.com/)的高性能HTTP Server，它能帮助你大幅度地提高网站的并发能力。

## 安装

使用composer引入拓展包：

```
$ composer require huang-yi/laravel-swoole-http
```

> 该拓展包依赖于Swoole，请务必确保你的机器安装上了Swoole拓展。快速安装命令：`pecl install swoole`，详情请参考[Swoole官方网站](https://wiki.swoole.com/wiki/page/6.html)。

## 注册服务

如果你正在使用Laravel框架开发应用，请在`config/app.php`的providers数组中添加服务提供器：

```php
[
    'providers' => [
        SwooleTW\Http\LaravelServiceProvider::class,
    ],
]
```

如果你正在使用Lumen框架开发应用，添加下面这行代码到`bootstrap/app.php`文件：

```php
$app->register(SwooleTW\Http\LumenServiceProvider::class);
```

## 配置信息

如果你想修改默认配置项，请运行以下命令，它生成配置文件`http.php`在`config/`文件夹下：

```
$ php artisan vendor:publish
```

`server.host`：swoole_http_server监听的IP地址。

`server.port`: swoole_http_server监听的端口。

`server.options`: `Swoole\Server`的配置项. 更多细节请阅读[官方文档](https://wiki.swoole.com/wiki/page/274.html).

例如你想调整max_request参数：

```php
[
    'server' => [
        'options' => [
            'max_request' => 1000,
        ],
    ]
]
```

## 命令

> swoole_http_server只能运行在cli模式下，因此该拓展包也提供了方便的artisan命令来做管理。

启动swoole_http_server：

```
$ php artisan swoole:http start
```

停止swoole_http_server：

```
$ php artisan swoole:http stop
```

重启swoole_http_server：

```
$ php artisan swoole:http restart
```

重载swoole_http_server：

```
$ php artisan swoole:http reload
```

## 配置Nginx

> swoole_http_server对Http协议的支持并不完整，建议仅作为应用服务器，并且在前端增加Nginx作为代理。

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /path/to/laravel/public;
    index index.php;

    location = /index.php {
        # 确保public目录下没有名字为not_exists的文件
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

        # 如果使用了https协议
        # proxy_set_header HTTPS "on";

        proxy_pass http://127.0.0.1:1215$suffix;
    }
}
```

### 注意事项

每次发布新代码需要重载或重启swoole_http_server，因为swoole_http_server启动时会提前加载应用框架，使其常驻内存，这也是swoole_http_server高性能的原因之一。
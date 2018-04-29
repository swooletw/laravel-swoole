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

## Features

* Run Laravel/Lumen application on top of Swoole.
* Sandbox mode to decrease technical gap for beginners.
* Support running websocket server in Laravel.
* Support `Socket.io` protocol.
* Support Swoole table.

## Documentation

Please see [Wiki](https://github.com/swooletw/laravel-swoole/wiki)

## Benchmark

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

## Support

Bugs and feature request are tracked on [Github](https://github.com/swooletw/laravel-swoole-http/issues).

## Credits

[Huang-Yi](https://github.com/huang-yi)

## License

The Laravel-Swoole package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

## Support on Beerpay
Hey dude! Help me out for a couple of :beers:!

[![Beerpay](https://beerpay.io/swooletw/laravel-swoole/badge.svg?style=beer-square)](https://beerpay.io/swooletw/laravel-swoole)  [![Beerpay](https://beerpay.io/swooletw/laravel-swoole/make-wish.svg?style=flat-square)](https://beerpay.io/swooletw/laravel-swoole?focus=wish)

# Laravel-Swoole

![php-badge](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)
[![packagist-badge](https://img.shields.io/packagist/v/swooletw/laravel-swoole.svg)](https://packagist.org/packages/swooletw/laravel-swoole)
[![Total Downloads](https://poser.pugx.org/swooletw/laravel-swoole/downloads)](https://packagist.org/packages/swooletw/laravel-swoole)
[![travis-badge](https://api.travis-ci.org/swooletw/laravel-swoole.svg?branch=master)](https://travis-ci.org/swooletw/laravel-swoole)

This package provides a high performance HTTP server to speed up your Laravel/Lumen application based on [Swoole](http://www.swoole.com/).

## Version Compatibility

| PHP     | Laravel | Lumen | Swoole  |
|:-------:|:-------:|:-----:|:-------:|
| >=7.1 | ~5.1    | ~5.1  | >=1.9.3 |

## Features

* Run **Laravel/Lumen** application on top of **Swoole**.
* Outstanding performance boosting up to **30x**.
* Sandbox mode to isolate app container.
* Support running websocket server in **Laravel**.
* Support `Socket.io` protocol.
* Support Swoole table for cross-process data sharing.

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

## Q&A

The common questions are collected in [Q&A](https://github.com/swooletw/laravel-swoole/wiki/Z4.-Q&A). You can go check if your question is listed in the document.

## Issues and Support

Please read [Issues Guideline](https://github.com/swooletw/laravel-swoole/wiki/Z2.-Issues-Guideline) before you submit an issue, thanks.

Bugs and feature request are tracked on [GitHub](https://github.com/swooletw/laravel-swoole/issues).

## Credits

[Huang-Yi](https://github.com/huang-yi), <a href="https://unisharp.com"><img src="https://i.imgur.com/TjyJIoO.png" width="160"></a>

## Alternatives

* [laravel-s](https://github.com/hhxsv5/laravel-s)

## License

The Laravel-Swoole package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

## Support on Beerpay
Hey dude! Help me out for a couple of :beers:!

[![Beerpay](https://beerpay.io/swooletw/laravel-swoole/badge.svg?style=beer-square)](https://beerpay.io/swooletw/laravel-swoole)  [![Beerpay](https://beerpay.io/swooletw/laravel-swoole/make-wish.svg?style=flat-square)](https://beerpay.io/swooletw/laravel-swoole?focus=wish)

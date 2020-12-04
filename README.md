# Laravel-Swoole

![php-badge](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)
[![packagist-badge](https://img.shields.io/packagist/v/swooletw/laravel-swoole.svg)](https://packagist.org/packages/swooletw/laravel-swoole)
[![Total Downloads](https://poser.pugx.org/swooletw/laravel-swoole/downloads)](https://packagist.org/packages/swooletw/laravel-swoole)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/swooletw/laravel-swoole/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/swooletw/laravel-swoole/?branch=master)
[![travis-badge](https://api.travis-ci.org/swooletw/laravel-swoole.svg?branch=master)](https://travis-ci.org/swooletw/laravel-swoole)

This package provides a high performance HTTP server to speed up your Laravel/Lumen application based on [Swoole](http://www.swoole.com/).

## Version Compatibility

| PHP     | Laravel | Lumen | Swoole  |
|:-------:|:-------:|:-----:|:-------:|
| >=7.2 | >=5.5    | >=5.5  | >=4.3.1 |

## Features

* Run **Laravel/Lumen** application on top of **Swoole**.
* Outstanding performance boosting up to **5x** faster.
* Sandbox mode to isolate app container.
* Support running websocket server in **Laravel**.
* Support `Socket.io` protocol.
* Support Swoole table for cross-process data sharing.

## Documentation

Please see [Wiki](https://github.com/swooletw/laravel-swoole/wiki)

## Benchmark

Test with clean Lumen 5.6, using DigitalOcean 3 CPUs / 1 GB Memory / PHP 7.2 / Ubuntu 16.04.4 x64

Benchmarking Tool: [wrk](https://github.com/wg/wrk)

```
wrk -t4 -c100 http://your.app
```

### Nginx with FPM

```
wrk -t4 -c10 http://lumen-swoole.local

Running 10s test @ http://lumen-swoole.local
  4 threads and 10 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     6.41ms    1.56ms  19.71ms   71.32%
    Req/Sec   312.99     28.71   373.00     72.00%
  12469 requests in 10.01s, 3.14MB read
Requests/sec:   1245.79
Transfer/sec:    321.12KB
```

### Swoole HTTP Server

```
wrk -t4 -c10 http://lumen-swoole.local:1215

Running 10s test @ http://lumen-swoole.local:1215
  4 threads and 10 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     2.39ms    4.88ms 105.21ms   94.55%
    Req/Sec     1.26k   197.13     1.85k    68.75%
  50248 requests in 10.02s, 10.88MB read
Requests/sec:   5016.94
Transfer/sec:      1.09MB
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

<?php

namespace SwooleTW\Http\Tests\Websocket\Middleware;

use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Http\Request;
use Mockery as m;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Websocket\Middleware\DecryptCookies;
use Symfony\Component\HttpFoundation\ParameterBag;

class DecrpytCookiesTest extends TestCase
{
    public function testDisableFor()
    {
        $middleware = $this->getMiddleware();
        $middleware->disableFor('foo');

        $this->assertTrue($middleware->isDisabled('foo'));
        $this->assertFalse($middleware->isDisabled('bar'));
    }

    public function testDecrypt()
    {
        $request = new Request;
        $request->cookies = new ParameterBag([
            'foo' => 'bar',
            'seafood' => 'sasaya',
            'aaa' => [
                'bbb' => 'ccc',
            ],
        ]);

        $encrypter = m::mock(EncrypterContract::class);
        $encrypter->shouldReceive('decrypt')
                  ->once()
                  ->with('sasaya', false);
        $encrypter->shouldReceive('decrypt')
                  ->once()
                  ->with('ccc', false);

        $middleware = $this->getMiddleware($encrypter);
        $middleware->disableFor('foo');

        $middleware->handle($request, function ($next) {
            return $next;
        });
    }

    protected function getMiddleware(EncrypterContract $encrypter = null)
    {
        return new DecryptCookies($encrypter ?: m::mock(EncrypterContract::class));
    }
}
<?php
namespace Rebet\Tests\Auth\Middleware;

use Rebet\Auth\Auth;
use Rebet\Auth\Middleware\Authenticate;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Tests\RebetTestCase;

class AuthenticateTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Authenticate::class, new Authenticate());
    }

    public function test_handle()
    {
        $middleware  = new Authenticate();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request  = $this->createRequestMock('/');
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());

        $request  = $this->createRequestMock('/', 'user');
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/signin', $response->getTargetUrl());

        $user = Auth::attempt($request, 'user@rebet.local', 'user');
        Auth::signin($request, $user, '/user/signin');

        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());
    }
}

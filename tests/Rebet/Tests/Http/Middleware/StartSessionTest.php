<?php
namespace Rebet\Tests\Http\Middleware;

use Rebet\Http\Middleware\StartSession;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Session\Session;
use Rebet\Tests\RebetTestCase;

class StartSessionTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(StartSession::class, new StartSession());
    }

    public function test_handleAndTerminate()
    {
        $middleware  = new StartSession();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request = Request::create('/');
        try {
            $request->session();
            $this->fail('Never execute.');
        } catch (\BadMethodCallException $e) {
            $this->assertSame('Session has not been set', $e->getMessage());
        }
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());
        $session = $request->session();
        $this->assertInstanceOf(Session::class, $session);
        $this->assertTrue($session->isStarted());

        $middleware->terminate($request, $response);
        $this->assertFalse($session->isStarted());
    }
}

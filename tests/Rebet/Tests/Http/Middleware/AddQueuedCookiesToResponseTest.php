<?php
namespace Rebet\Tests\Auth\Middleware;

use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Middleware\AddQueuedCookiesToResponse;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Tests\RebetTestCase;

class AddQueuedCookiesToResponseTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(AddQueuedCookiesToResponse::class, new AddQueuedCookiesToResponse());
    }

    public function test_handle()
    {
        $middleware  = new AddQueuedCookiesToResponse();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        Cookie::set('key', 'value');
        Cookie::set('test', 'unit');

        $request  = $this->createRequestMock('/');
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());

        $this->assertEquals(
            [
                new Cookie('key', 'value'),
                new Cookie('test', 'unit'),
                new Cookie('_beacon', '1'),
            ],
            $response->headers->getCookies()
        );
    }
}

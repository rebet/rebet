<?php

declare(strict_types=1);

namespace Rebet\Tests\Middleware\Routing;

use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Middleware\Routing\AddQueuedCookiesToResponse;
use Rebet\Tests\RebetTestCase;

class AddQueuedCookiesToResponseTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(AddQueuedCookiesToResponse::class, new AddQueuedCookiesToResponse());
    }

    public function test_handle(): void
    {
        $middleware  = new AddQueuedCookiesToResponse();
        $destination = fn($request) => Responder::toResponse('OK');

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
            ],
            $response->headers->getCookies(),
        );
    }
}

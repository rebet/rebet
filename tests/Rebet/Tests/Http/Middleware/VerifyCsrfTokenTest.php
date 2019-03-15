<?php
namespace Rebet\Tests\Http\Middleware;

use Rebet\Http\Middleware\VerifyCsrfToken;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Tests\RebetTestCase;

class VerifyCsrfTokenTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(VerifyCsrfToken::class, new VerifyCsrfToken());
    }

    public function test_handle()
    {
        $middleware  = new VerifyCsrfToken();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        foreach (['HEAD', 'GET', 'OPTIONS'] as $method) {
            $request  = $this->createRequestMock('/', null, 'web', $method);
            $response = $middleware->handle($request, $destination);
            $this->assertInstanceOf(BasicResponse::class, $response);
            $this->assertSame(200, $response->getStatusCode());
        }
    }
}

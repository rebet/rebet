<?php
namespace Rebet\Tests\Middleware\Routing;

use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Middleware\Routing\RestoreInheritData;
use Rebet\Tests\RebetTestCase;
use Rebet\View\View;

class RestoreInheritDataTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(RestoreInheritData::class, new RestoreInheritData());
    }

    public function test_handle()
    {
        $middleware  = new RestoreInheritData();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request = $this->createRequestMock('/');
        $session = $request->session();
        $session->saveInheritData('input', ['name' => 'Name']);
        $session->saveInheritData('errors', ['name' => ['Invalid']]);
        $this->assertSame([], $request->input());
        $this->assertSame(null, View::shared('errors'));
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());
        $this->assertSame(['name' => 'Name'], $request->input());
        $this->assertSame(['name' => ['Invalid']], View::shared('errors'));
    }
}

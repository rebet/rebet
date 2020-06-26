<?php
namespace Rebet\Tests\Middleware\Routing;

use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Middleware\Routing\AddGlobalShareVariableToView;
use Rebet\Tests\RebetTestCase;
use Rebet\View\View;

class AddGlobalShareVariableToViewTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(AddGlobalShareVariableToView::class, new AddGlobalShareVariableToView());
    }

    public function test_handle()
    {
        $middleware  = new AddGlobalShareVariableToView();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request  = $this->createRequestMock('/', null, 'web', 'GET', '/prefix');
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());

        $this->assertSame($request, View::shared('request'));
        $this->assertSame($request->route, View::shared('route'));
        $this->assertSame($request->getRoutePrefix(), View::shared('prefix'));
        $this->assertSame($request->session(), View::shared('session'));
    }
}

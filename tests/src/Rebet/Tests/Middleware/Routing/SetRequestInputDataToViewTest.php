<?php
namespace Rebet\Tests\Middleware\Routing;

use Rebet\Tools\Tinker\Tinker;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Middleware\Routing\SetRequestInputDataToView;
use Rebet\Tests\RebetTestCase;
use Rebet\View\View;

class SetRequestInputDataToViewTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(SetRequestInputDataToView::class, new SetRequestInputDataToView());
    }

    public function test_handle()
    {
        $middleware  = new SetRequestInputDataToView();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request = $this->createRequestMock('/');
        $request->query->set('query', 'Q');
        $request->request->set('request', 'R');
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());
        $this->assertInstanceOf(Tinker::class, View::shared('input'));
        $this->assertSame([
            'request' => 'R',
            'query'   => 'Q',
        ], View::shared('input')->return());
    }
}

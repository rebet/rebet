<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Tests\RebetTestCase;

class ClosureRouteTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ClosureRoute::class, new ClosureRoute(['GET'], '/', function () { return 'Hello World.'; }));
    }

    public function test_createRouteAction()
    {
        $route   = new ClosureRoute(['GET'], '/foo', function () { return 'Hello World.'; });
        $request = $this->createRequestMock('/foo');
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Hello World.', $response->getContent());
    }

    public function test_terminate()
    {
        $route   = new ClosureRoute(['GET'], '/', function () { return 'Hello World.'; });
        $request = $this->createRequestMock('/foo');
        $this->assertNull($route->terminate($request, Responder::toResponse('foo')));
    }
}

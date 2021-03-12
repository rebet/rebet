<?php
namespace Rebet\Tests\Routing\Route;

use App\Controller\TestController;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Route\ControllerRoute;
use Rebet\Tests\RebetTestCase;

class ControllerRouteTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ControllerRoute::class, new ControllerRoute('/test', TestController::class));
        $this->assertInstanceOf(ControllerRoute::class, new ControllerRoute('/test', 'TestController'));
    }

    public function test_terminate()
    {
        $route   = new ControllerRoute('/test', TestController::class);
        $request = $this->createRequestMock('/test', null, 'web', 'web', 'GET', '', $route);
        $this->assertNull($route->terminate($request, Responder::toResponse('test')));
    }

    public function test_routing()
    {
        $route   = new ControllerRoute('/test', TestController::class);

        $request = $this->createRequestMock('/test/public-call', null, 'web', 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: publicCall', $response->getContent());

        $request = $this->createRequestMock('/test/with-param/123', null, 'web', 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: withParam - 123', $response->getContent());
    }

    public function test_getControllerName()
    {
        $route = new ControllerRoute('/test', TestController::class);
        $this->assertSame(TestController::class, $route->getControllerName());
        $this->assertSame('TestController', $route->getControllerName(false));

        $route = new ControllerRoute('/test', 'TestController');
        $this->assertSame(TestController::class, $route->getControllerName());
        $this->assertSame('TestController', $route->getControllerName(false));
    }
}

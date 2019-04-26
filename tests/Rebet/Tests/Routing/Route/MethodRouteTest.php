<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Route\MethodRoute;
use Rebet\Tests\RebetTestCase;

class MethodRouteTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(MethodRoute::class, new MethodRoute(['GET'], '/', 'Rebet\Tests\Mock\Controller\TestController::index'));
        $this->assertInstanceOf(MethodRoute::class, new MethodRoute(['GET'], '/', '@controller\TestController::index'));
        $this->assertInstanceOf(MethodRoute::class, new MethodRoute(['GET'], '/', 'TestController::index'));
    }

    public function test_routing()
    {
        $route   = new MethodRoute(['GET'], '/foo', 'Rebet\Tests\Mock\Controller\TestController::index');
        $request = $this->createRequestMock('/foo', null, 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: index', $response->getContent());

        $route   = new MethodRoute(['GET'], '/foo', '@controller\TestController::index');
        $request = $this->createRequestMock('/foo', null, 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: index', $response->getContent());

        $route   = new MethodRoute(['GET'], '/foo', 'TestController::index');
        $request = $this->createRequestMock('/foo', null, 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: index', $response->getContent());

        $route   = new MethodRoute(['GET'], '/foo', 'TestController::staticCall');
        $request = $this->createRequestMock('/foo', null, 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: staticCall', $response->getContent());

        $route   = new MethodRoute(['GET'], '/foo', 'TestController::privateCall');
        $route->accessible(true);
        $request = $this->createRequestMock('/foo', null, 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: privateCall', $response->getContent());
    }
}

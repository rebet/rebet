<?php
namespace Rebet\Tests\Routing;

use Rebet\Annotation\AnnotatedMethod;
use Rebet\Application\App;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Routing\RouteAction;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;

class RouteActionTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(RouteAction::class, $this->createRouteActionBasedClosureMock(function () { return 'Hello'; }));
    }

    public function test___construct_error()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid type of reflector.");

        $action = function () { return 'Hello'; };
        $route  = new ClosureRoute([], '/', $action);
        $this->assertInstanceOf(RouteAction::class, new RouteAction($route, $action));
    }

    protected function createRouteActionBasedClosureMock(callable $action) : RouteAction
    {
        $action = \Closure::fromCallable($action);
        $route  = new ClosureRoute([], '/', $action);
        return new RouteAction($route, new \ReflectionFunction($action));
    }

    protected function createRouteActionBasedControllerMock(string $url) : array
    {
        $route   = new ConventionalRoute();
        $request = $this->createRequestMock($url, null, 'web', 'web', 'GET', '', $route);
        $route->match($request);
        $route_action = Reflector::get($route, 'route_action', null, true);
        $controller   = Reflector::get($route_action, 'instance', null, true);
        return [$request, $route, $route_action, $controller];
    }

    public function test_invoke()
    {
        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/index');

        $this->assertSame(0, $controller->before_count);
        $this->assertSame(0, $controller->after_count);
        $response = $route_action->invoke($request);
        $this->assertSame(1, $controller->before_count);
        $this->assertSame(1, $controller->after_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: index', $response->getContent());
    }

    public function test_invoke_withParam()
    {
        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/with-param/123');

        $response = $route_action->invoke($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: withParam - 123', $response->getContent());


        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/with-optional-param/123');

        $response = $route_action->invoke($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: withOptionalParam - 123', $response->getContent());


        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/with-optional-param');

        $response = $route_action->invoke($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());


        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/with-convert-enum-param/1');

        App::setLocale('ja');
        $response = $route_action->invoke($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Controller: withConvertEnumParam - 男性', $response->getContent());
    }

    public function test_invoke_withParam_error()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::withParam not found. Routing parameter 'id' is requierd.");

        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/with-param/123');
        $request->attributes->set('id', null);
        $response = $route_action->invoke($request);
    }

    public function test_invoke_withParam_convertError()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::withConvertEnumParam not found. Routing parameter gender(=3) can not convert to App\Enum\Gender.");

        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/with-convert-enum-param/3');

        $response = $route_action->invoke($request);
    }

    public function test_getAnnotatedMethod()
    {
        $route_action = $this->createRouteActionBasedClosureMock(function () { return 'Hello'; });
        $this->assertNull($route_action->getAnnotatedMethod());


        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/annotation-method-get');

        $am = $route_action->getAnnotatedMethod();
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
        $method = $am->annotation(Method::class);
        $this->assertInstanceOf(Method::class, $method);
        $this->assertSame(['GET'], $method->allows);
    }

    public function test_annotation()
    {
        $route_action = $this->createRouteActionBasedClosureMock(function () { return 'Hello'; });
        $this->assertNull($route_action->annotation(Method::class));


        [$request, $route, $route_action, $controller] = $this->createRouteActionBasedControllerMock('/test/annotation-method-get');

        $method = $route_action->annotation(Method::class);
        $this->assertInstanceOf(Method::class, $method);
        $this->assertSame(['GET'], $method->allows);
    }
}

<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Tools\Namespaces;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Tests\Mock\Controller\TestController;
use Rebet\Tests\Mock\Controller\TopController;
use Rebet\Tests\RebetTestCase;

class ConventionalRouteTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ConventionalRoute::class, new ConventionalRoute());

        $route = new ConventionalRoute([
            'namespace'                  => '@mock',
            'default_part_of_controller' => 'admin',
            'default_part_of_action'     => 'login',
            'uri_snake_separator'        => '_',
            'controller_suffix'          => '',
            'action_suffix'              => 'Action',
            'aliases'                    => [
                '/howto' => '/misc/howto'
            ],
            'accessible'                 => true,
        ]);
        $this->assertSame(Namespaces::resolve('@mock'), Reflector::get($route, 'namespace', null, true));
        $this->assertSame('admin', Reflector::get($route, 'default_part_of_controller', null, true));
        $this->assertSame('login', Reflector::get($route, 'default_part_of_action', null, true));
        $this->assertSame('_', Reflector::get($route, 'uri_snake_separator', null, true));
        $this->assertSame('', Reflector::get($route, 'controller_suffix', null, true));
        $this->assertSame('Action', Reflector::get($route, 'action_suffix', null, true));
        $this->assertSame(['/howto' => '/misc/howto'], Reflector::get($route, 'aliases', null, true));
        $this->assertSame(true, Reflector::get($route, 'accessible', null, true));
    }

    public function test_terminate()
    {
        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test', null, 'web', 'GET', '', $route);
        $this->assertNull($route->terminate($request, Responder::toResponse('test')));
    }

    public function dataRoutings() : array
    {
        return [
            ['Top: index', '/'],
            ['Controller: publicCall', '/test/public-call'],
            ['Controller: withParam - 123', '/test/with-param/123'],
            ['Controller: withOptionalParam - default', '/test/with-optional-param'],
            ['Controller: withParam - 123', '/param/123', ['aliases' => [ '/param' => '/test/with-param' ]]],
            ['Controller: privateCall', '/test/private-call', ['accessible' => true]],
            ['Controller: annotationAliasOnly', '/alias', ['aliases' => ['/alias' => '/test/annotation-alias-only']]],
            ['Controller: annotationWhere - abc', '/test/annotation-where/abc'],
            ['Controller: annotationChannelApi', '/test/annotation-channel-api', [], 'api'],
            ['Controller: annotationMethodGet', '/test/annotation-method-get'],

        ];
    }

    /**
     * @dataProvider dataRoutings
     */
    public function test_routing($expect, $request_path, $option = [], $channel = 'web', $method = 'GET', $setuper = null)
    {
        $route   = new ConventionalRoute($option);
        $request = $this->createRequestMock($request_path, null, $channel, $method, '', $route);
        if ($setuper) {
            $setuper($route, $request);
        }
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame($expect, $response->getContent());
    }

    public function test_routing_notFound()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Controller [ Rebet\Tests\Mock\Controller\InvalidController ] can not instantiate.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/invalid', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_actionNotFound()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ Rebet\Tests\Mock\Controller\TestController::invalid ] not exists.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/invalid', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_actionNotAccessible()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ Rebet\Tests\Mock\Controller\TestController::privateCall ] not accessible.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/private-call', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_annotationNotRouting()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ Rebet\Tests\Mock\Controller\TestController::annotationNotRouting ] is not routing.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-not-routing', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_accesptOnlyAliasAccess()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ Rebet\Tests\Mock\Controller\TestController::annotationAliasOnly ] accespt only alias access.");

        $route   = new ConventionalRoute(['aliases' => ['/alias' => '/test/annotation-alias-only']]);
        $request = $this->createRequestMock('/test/annotation-alias-only', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_invlidRouteParameter_requierd()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Requierd parameter 'id' on [ Rebet\Tests\Mock\Controller\TestController::withParam ] not supplied.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/with-param/', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_invlidRouteParameter_where()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: Rebet\Tests\Mock\Controller\TestController::withParam not found. Routing parameter 'id' value '123' not match /^[a-z]*$/.");

        $route   = new ConventionalRoute();
        $route->where('id', '/^[a-z]*$/');
        $request = $this->createRequestMock('/test/with-param/123', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_invlidRouteParameter_annotationWhere()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: Rebet\Tests\Mock\Controller\TestController::annotationWhere not found. Routing parameter 'id' value '123' not match /^[a-zA-Z]+$/.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-where/123', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_invlidChannel()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: Rebet\Tests\Mock\Controller\TestController::annotationChannelApi not found. Routing channel 'web' not allowed or not annotated channel meta info.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-channel-api', null, 'web', 'GET', '', $route);
        $route->match($request);
    }

    public function test_routing_invlidMethod()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: Rebet\Tests\Mock\Controller\TestController::annotationMethodGet not found. Routing method 'POST' not allowed.");

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-method-get', null, 'web', 'POST', '', $route);
        $route->match($request);
    }

    public function dataDefaultViews() : array
    {
        return [
            ['/test/public-call', '/test/public-call'],
            ['/test/with-param', '/test/with-param/123'],
            ['/test/with-param', '/param/123', ['aliases' => [ '/param' => '/test/with-param' ]]],
        ];
    }

    /**
     * @dataProvider dataDefaultViews
     */
    public function test_defaultView($expect, $uri, $option = [])
    {
        $route   = new ConventionalRoute($option);
        $request = $this->createRequestMock($uri, null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame($expect, $route->defaultView());
    }

    public function dataGetControllerNames() : array
    {
        return [
            [TestController::class, '/test/public-call'],
            ['TestController', '/test/public-call', false],
            [TopController::class, '/'],
            [TopController::class, '/top'],
            [TestController::class, '/param/123', true, ['aliases' => [ '/param' => '/test/with-param' ]]],
        ];
    }

    /**
     * @dataProvider dataGetControllerNames
     */
    public function test_getControllerName($expect, $uri, $with_namespace = true, $option = [])
    {
        $route   = new ConventionalRoute($option);
        $request = $this->createRequestMock($uri, null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame($expect, $route->getControllerName($with_namespace));
    }

    public function dataGetActionNames() : array
    {
        return [
            ['publicCall', '/test/public-call'],
            ['index', '/'],
            ['index', '/top'],
            ['withParam', '/param/123', ['aliases' => [ '/param' => '/test/with-param' ]]],
        ];
    }

    /**
     * @dataProvider dataGetActionNames
     */
    public function test_getActionName($expect, $uri, $option = [])
    {
        $route   = new ConventionalRoute($option);
        $request = $this->createRequestMock($uri, null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame($expect, $route->getActionName());
    }

    public function dataGetAliasNames() : array
    {
        return [
            [null, '/test/public-call'],
            [null, '/'],
            [null, '/top'],
            ['/param', '/param/123', ['aliases' => [ '/param' => '/test/with-param' ]]],
        ];
    }

    /**
     * @dataProvider dataGetAliasNames
     */
    public function test_getAliasName($expect, $uri, $option = [])
    {
        $route   = new ConventionalRoute($option);
        $request = $this->createRequestMock($uri, null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame($expect, $route->getAliasName());
    }

    public function test___toString()
    {
        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/public-call', null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame('Route: Rebet\Tests\Mock\Controller\TestController::publicCall', $route->__toString());
    }

    public function test_accessible()
    {
        $route = new ConventionalRoute();
        $this->assertFalse(Reflector::get($route, 'accessible', null, true));
        $this->assertInstanceOf(ConventionalRoute::class, $route->accessible(true));
        $this->assertTrue(Reflector::get($route, 'accessible', null, true));
    }

    public function test_aliases()
    {
        $route = new ConventionalRoute();
        $this->assertSame([], Reflector::get($route, 'aliases', null, true));
        $this->assertInstanceOf(ConventionalRoute::class, $route->aliases('/alias', '/real/path'));
        $this->assertSame(['/alias' => '/real/path'], Reflector::get($route, 'aliases', null, true));
    }
}

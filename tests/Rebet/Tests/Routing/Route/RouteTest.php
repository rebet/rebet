<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Annotation\AnnotatedMethod;
use Rebet\Common\Reflector;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Middleware\AddGlobalShareVariableToView;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Tests\RebetTestCase;

class RouteTest extends RebetTestCase
{
    public function test_where()
    {
        $route = new ClosureRoute(['GET'], '/foo', function () { return 'Hello World.'; });
        $this->assertSame([], Reflector::get($route, 'wheres', null, true));
        $this->assertInstanceOf(ClosureRoute::class, $route->where('id', '/[0-9]+/'));
        $this->assertSame(['id' => '/[0-9]+/'], Reflector::get($route, 'wheres', null, true));
        $route->where(['page' => '/[0-9]+/']);
        $this->assertSame(['id' => '/[0-9]+/', 'page' => '/[0-9]+/'], Reflector::get($route, 'wheres', null, true));
    }

    public function test___invoke()
    {
        $route   = new ClosureRoute(['GET'], '/foo', function () { return 'Hello World.'; });
        $request = $this->createRequestMock('/foo', null, 'web', 'GET', '', $route);
        $route->match($request);
        $response = $route->__invoke($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Hello World.', $response->getContent());
    }

    public function test_getAnnotatedMethod()
    {
        $route   = new ConventionalRoute();
        $this->assertNull($route->getAnnotatedMethod());
        $request = $this->createRequestMock('/test/annotation-method-get', null, 'web', 'GET', '', $route);
        $route->match($request);
        $am = $route->getAnnotatedMethod();
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
        $this->assertInstanceOf(Method::class, $am->annotation(Method::class));
    }

    public function test_annotation()
    {
        $route   = new ConventionalRoute();
        $this->assertNull($route->annotation(Method::class));
        $request = $this->createRequestMock('/test/annotation-method-get', null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertInstanceOf(Method::class, $route->annotation(Method::class));
    }

    public function test_middlewares()
    {
        $route   = new ClosureRoute(['GET'], '/foo', function () { return 'Hello World.'; });
        $this->assertSame([], $route->middlewares());
        $middleware = new AddGlobalShareVariableToView();
        $this->assertInstanceOf(ClosureRoute::class, $route->middlewares($middleware));
        $this->assertSame([$middleware], $route->middlewares());
    }

    public function test_roles()
    {
        $route   = new ClosureRoute(['GET'], '/foo', function () { return 'Hello World.'; });
        $this->assertSame([], $route->roles());
        $this->assertInstanceOf(ClosureRoute::class, $route->roles('user'));
        $this->assertSame(['user'], $route->roles());
        $this->assertInstanceOf(ClosureRoute::class, $route->roles('admin', 'guest'));
        $this->assertSame(['admin', 'guest'], $route->roles());

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-role-user', null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame(['user'], $route->roles());
    }

    public function test_auth()
    {
        $route   = new ClosureRoute(['GET'], '/foo', function () { return 'Hello World.'; });
        $this->assertSame(null, $route->auth());
        $this->assertInstanceOf(ClosureRoute::class, $route->auth('web'));
        $this->assertSame('web', $route->auth());

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-authenticator-api', null, 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame('api', $route->auth());
    }
}

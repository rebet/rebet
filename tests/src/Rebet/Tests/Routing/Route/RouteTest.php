<?php

declare(strict_types=1);

namespace Rebet\Tests\Routing\Route;

use Rebet\Attribute\AttributedMethod;
use Rebet\Http\Response\BasicResponse;
use Rebet\Middleware\Routing\AddGlobalShareVariableToView;
use Rebet\Routing\Attribute\Method;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Reflection\Reflector;

class RouteTest extends RebetTestCase
{
    public function test_where(): void
    {
        $route = new ClosureRoute(['GET'], '/foo', fn() => 'Hello World.');
        $this->assertSame([], Reflector::get($route, 'wheres', null, true));
        $this->assertInstanceOf(ClosureRoute::class, $route->where('id', '/[0-9]+/'));
        $this->assertSame(['id' => '/[0-9]+/'], Reflector::get($route, 'wheres', null, true));
        $route->where(['page' => '/[0-9]+/']);
        $this->assertSame(['id' => '/[0-9]+/', 'page' => '/[0-9]+/'], Reflector::get($route, 'wheres', null, true));
    }

    public function test___invoke(): void
    {
        $route   = new ClosureRoute(['GET'], '/foo', fn() => 'Hello World.');
        $request = $this->createRequestMock('/foo', null, 'web', 'web', 'GET', '', $route);
        $route->match($request);
        $response = $route->__invoke($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Hello World.', $response->getContent());
    }

    public function test_getAttributedMethod(): void
    {
        $route = new ConventionalRoute();
        $this->assertNull($route->getAttributedMethod());
        $request = $this->createRequestMock('/test/annotation-method-get', null, 'web', 'web', 'GET', '', $route);
        $route->match($request);
        $am = $route->getAttributedMethod();
        $this->assertInstanceOf(AttributedMethod::class, $am);
        $this->assertInstanceOf(Method::class, $am->attribute(Method::class));
    }

    public function test_attribute(): void
    {
        $route = new ConventionalRoute();
        $this->assertNull($route->attribute(Method::class));
        $request = $this->createRequestMock('/test/annotation-method-get', null, 'web', 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertInstanceOf(Method::class, $route->attribute(Method::class));
    }

    public function test_middlewares(): void
    {
        $route = new ClosureRoute(['GET'], '/foo', fn() => 'Hello World.');
        $this->assertSame([], $route->middlewares());
        $middleware = new AddGlobalShareVariableToView();
        $this->assertInstanceOf(ClosureRoute::class, $route->middlewares($middleware));
        $this->assertSame([$middleware], $route->middlewares());
    }

    public function test_roles(): void
    {
        $route = new ClosureRoute(['GET'], '/foo', fn() => 'Hello World.');
        $this->assertSame([], $route->roles());
        $this->assertInstanceOf(ClosureRoute::class, $route->roles('user'));
        $this->assertSame(['user'], $route->roles());
        $this->assertInstanceOf(ClosureRoute::class, $route->roles('admin', 'guest'));
        $this->assertSame(['admin', 'guest'], $route->roles());

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-role-user', null, 'web', 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame(['user'], $route->roles());
    }

    public function test_guard(): void
    {
        $route = new ClosureRoute(['GET'], '/foo', fn() => 'Hello World.');
        $this->assertSame(null, $route->guard());
        $this->assertInstanceOf(ClosureRoute::class, $route->guard('web'));
        $this->assertSame('web', $route->guard());

        $route   = new ConventionalRoute();
        $request = $this->createRequestMock('/test/annotation-guard-api', null, 'web', 'web', 'GET', '', $route);
        $route->match($request);
        $this->assertSame('api', $route->guard());
    }
}

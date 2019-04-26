<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Route\DeclarativeRoute;
use Rebet\Tests\RebetTestCase;

class DeclarativeRouteTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(DeclarativeRoute::class, new ClosureRoute(['GET'], '/', function () { return 'Hello World.'; }));
    }

    public function test___toString()
    {
        $route = new ClosureRoute(['GET', 'HEAD'], '/path', function () { return 'Hello World.'; });
        $this->assertSame('ClosureRoute: [GET|HEAD] /path', $route->__toString());

        $route = new ClosureRoute(['GET', 'HEAD'], '/path', function () { return 'Hello World.'; });
        $route->where('id', '/[0-9]+/');
        $this->assertSame('ClosureRoute: [GET|HEAD] /path where {"id":"\/[0-9]+\/"}', $route->__toString());
    }

    public function dataDefaultViews() : array
    {
        return [
            ['/path', '/path'],
            ['/path/to', '/path/to'],
            ['/path/to', '/path/to/{id}'],
            ['/article', '/article/{year}/{month?}/{day?}'],
            ['/path/from', '/path/from/{from}/to/{to}'],
        ];
    }

    /**
     * @dataProvider dataDefaultViews
     */
    public function test_defaultView($expect, $uri)
    {
        $route = new ClosureRoute([], $uri, function () { return 'Hello World.'; });
        $this->assertSame($expect, $route->defaultView());
    }
}

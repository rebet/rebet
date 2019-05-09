<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Config\Config;
use Rebet\Foundation\App;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Route\ViewRoute;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class ViewRouteTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::path('/resources/views/blade')],
                'cache_path' => 'vfs://root/cache',
            ],
        ]);
    }

    public function test___construct()
    {
        $route = new ViewRoute('/welcome/{name}', '/welcome');
        $this->assertInstanceOf(ViewRoute::class, $route);
    }

    public function test_routing()
    {
        $route = new ViewRoute('/welcome/{name}', '/welcome');
        $this->assertInstanceOf(ViewRoute::class, $route);
        $request = $this->createRequestMock('/welcome/Bob');
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Hello, Bob.', $response->getContent());
    }

    /**
     * @expectedException Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage View route [/nothing] (possible: /nothing) not found. An exception occurred while processing the view.
     */
    public function test_routing_viewNotFound()
    {
        $route   = new ViewRoute('/nothing', '/nothing');
        $request = $this->createRequestMock('/nothing');
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
    }
}

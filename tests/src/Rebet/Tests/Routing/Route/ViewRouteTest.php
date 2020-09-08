<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Application\App;
use Rebet\Config\Config;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Route\ViewRoute;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class ViewRouteTest extends RebetTestCase
{
    protected function setUp() : void
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
                'view_path'  => [App::structure()->views('/blade')],
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

    public function test_routing_viewNotFound()
    {
        $this->expectException(RouteNotFoundException::class);

        $route   = new ViewRoute('/nothing', '/nothing');
        $request = $this->createRequestMock('/nothing');
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
    }
}

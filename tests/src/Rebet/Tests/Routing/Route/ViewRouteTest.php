<?php

declare(strict_types=1);

namespace Rebet\Tests\Routing\Route;

use Override;
use Rebet\Application\App;
use Rebet\Http\Response\BasicResponse;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Route\ViewRoute;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class ViewRouteTest extends RebetTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Config::application([
            View::class  => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::structure()->views('/blade')],
                'cache_path' => static::makeSubWorkingDir('cache'),
            ],
        ]);
    }

    public function test___construct(): void
    {
        $route = new ViewRoute('/welcome/{name}', '/welcome');
        $this->assertInstanceOf(ViewRoute::class, $route);
    }

    public function test_routing(): void
    {
        $route = new ViewRoute('/welcome/{name}', '/welcome');
        $this->assertInstanceOf(ViewRoute::class, $route);
        $request = $this->createRequestMock('/welcome/Bob');
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Hello, Bob.', $response->getContent());
    }

    public function test_routing_viewNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);

        $route   = new ViewRoute('/nothing', '/nothing');
        $request = $this->createRequestMock('/nothing');
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
    }
}

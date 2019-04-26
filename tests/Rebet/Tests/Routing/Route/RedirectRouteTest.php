<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Http\Response\RedirectResponse;
use Rebet\Routing\Route\RedirectRoute;
use Rebet\Tests\RebetTestCase;

class RedirectRouteTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(RedirectRoute::class, new RedirectRoute('/from', '/redirect/to'));
    }

    // Router::redirect('/redirect', '/destination');
    // Router::redirect('/redirect/with-param/replace/{id}', '/destination/{id}');
    // Router::redirect('/redirect/with-param/query/{id}', '/destination');

    // Router::redirect('/redirect/with-optional-param/replace/{id?}', '/destination/{id}');
    // Router::redirect('/redirect/with-multi-param/{from?}/{to?}', '/destination/{from}/{to}');
    // Router::redirect('/redirect/with-multi-param/mix/{from}/{to}', '/destination/{from}');
    // Router::redirect('/redirect/query', '/destination', ['page' => 1]);
    // Router::redirect('/redirect/query/with-param/{id}', '/destination', ['page' => 1]);
    // Router::redirect('/redirect/query/inline/with-param/{id}', '/destination?page=1');

    public function dataRoutings() : array
    {
        return [
            ['/destination', '/redirect', '/destination', '/redirect'],
            ['/destination/123', '/redirect/{id}', '/destination/{id}', '/redirect/123'],
            ['/destination?id=123', '/redirect/{id}', '/destination', '/redirect/123'],
            ['/destination/123', '/redirect/{id?}', '/destination/{id}', '/redirect/123'],
            ['/destination', '/redirect/{id?}', '/destination/{id}', '/redirect'],
            ['/destination/a/b', '/redirect/{from?}/{to?}', '/destination/{from}/{to}', '/redirect/a/b'],
            ['/destination/a?to=b', '/redirect/{from}/{to}', '/destination/{from}', '/redirect/a/b'],
            ['/destination?page=1', '/redirect', '/destination', '/redirect', ['page' => 1]],
            ['/destination?page=1&id=123', '/redirect/{id}', '/destination', '/redirect/123', ['page' => 1]],
            ['/destination?page=1&id=123', '/redirect/{id}', '/destination?page=1', '/redirect/123'],
            ['/destination', '/redirect', '/destination', '/redirect', [], 301],
        ];
    }

    /**
     * @dataProvider dataRoutings
     */
    public function test_routing($expect, $uri, $destination, $request_uri, $query = [], $status = 302)
    {
        $route   = new RedirectRoute($uri, $destination, $query, $status);
        $request = $this->createRequestMock($request_uri, null, 'web', 'GET', '', $route);
        $this->assertTrue($route->match($request));
        $response = $route->handle($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expect, $response->getTargetUrl());
        $this->assertSame($status, $response->getStatusCode());
    }

    public function test___toString()
    {
        $route = new RedirectRoute('/from', '/redirect/to');
        $this->assertSame('RedirectRoute: [ALL] /from redirect to /redirect/to (status: 302)', $route->__toString());
    }
}

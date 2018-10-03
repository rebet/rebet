<?php
namespace Rebet\Tests\Log;

use Rebet\Tests\RebetTestCase;
use Rebet\Tests\StderrCapture;

use Rebet\Routing\Router;

use Rebet\Common\System;
use Rebet\Config\App;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Http\Request;
use Rebet\Http\BasicResponse;

class RouterTest extends RebetTestCase
{
    public function setUp()
    {
        System::initMock();
        Config::clear();
        App::setTimezone('UTC');
        App::setSurface('web');
        Config::application([
            Router::class => [
                'middlewares!'   => [],
                'default_route!' => null,
                'fallback!'      => null,
            ]
        ]);
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        Router::rules('web', function () {
            Router::get('/', function () {
                return 'Content: /';
            });

            Router::get('/get', function () {
                return 'Content: /get';
            });

            Router::post('/post', function () {
                return 'Content: /post';
            });

            Router::put('/put', function () {
                return 'Content: /put';
            });

            Router::patch('/patch', function () {
                return 'Content: /patch';
            });

            Router::delete('/delete', function () {
                return 'Content: /delete';
            });

            Router::options('/options', function () {
                return 'Content: /options';
            });

            Router::any('/any', function () {
                return 'Content: /any';
            });

            Router::match(['GET', 'HEAD', 'POST'], '/match/get-head-post', function () {
                return 'Content: /match/get-head-post';
            });

            Router::fallback(function ($request, $route, $e) {
                throw $e;
            });
        });
    }
    
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Routing rules are defined without Router::rules(). You should wrap rules by Router::rules().
     */
    public function test_invalidRuleDefine_match()
    {
        Router::match('GET', '/get', function () {
            return 'Content: /get';
        });
    }
    
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Routing fallback rules are defined without Router::rules(). You should wrap rules by Router::rules().
     */
    public function test_invalidRuleDefine_fallback()
    {
        Router::fallback(function ($request, $route, $e) {
            throw $e;
        });
    }
    
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Routing default rules are defined without Router::rules(). You should wrap rules by Router::rules().
     */
    public function test_invalidRuleDefine_default()
    {
        Router::default(function () {
            return 'default route.';
        });
    }

    public function test_routing_root()
    {
        $response = Router::handle(Request::create('/'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /', $response->getContent());
    }
    
    public function test_routing_get()
    {
        $response = Router::handle(Request::create('/get'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /get', $response->getContent());

        $response = Router::handle(Request::create('/get', 'HEAD'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route [GET|HEAD] /get not found. Invalid method POST given.
     */
    public function test_routing_get_invalidMethod()
    {
        $response = Router::handle(Request::create('/get', 'POST'));
    }
    
    public function test_routing_post()
    {
        $response = Router::handle(Request::create('/post', 'POST'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /post', $response->getContent());
    }

    public function test_routing_put()
    {
        $response = Router::handle(Request::create('/put', 'PUT'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /put', $response->getContent());
    }

    public function test_routing_patch()
    {
        $response = Router::handle(Request::create('/patch', 'PATCH'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /patch', $response->getContent());
    }

    public function test_routing_delete()
    {
        $response = Router::handle(Request::create('/delete', 'DELETE'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /delete', $response->getContent());
    }

    public function test_routing_options()
    {
        $response = Router::handle(Request::create('/options', 'OPTIONS'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /options', $response->getContent());
    }

    public function test_routing_any()
    {
        $response = Router::handle(Request::create('/any'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /any', $response->getContent());

        $response = Router::handle(Request::create('/any', 'HEAD'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('', $response->getContent());

        $response = Router::handle(Request::create('/any', 'POST'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /any', $response->getContent());

        $response = Router::handle(Request::create('/any', 'PUT'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /any', $response->getContent());

        $response = Router::handle(Request::create('/any', 'PATCH'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /any', $response->getContent());

        $response = Router::handle(Request::create('/any', 'DELETE'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /any', $response->getContent());

        $response = Router::handle(Request::create('/any', 'OPTIONS'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /any', $response->getContent());
    }
    
    public function test_routing_match()
    {
        $response = Router::handle(Request::create('/match/get-head-post'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /match/get-head-post', $response->getContent());

        $response = Router::handle(Request::create('/match/get-head-post', 'HEAD'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('', $response->getContent());

        $response = Router::handle(Request::create('/match/get-head-post', 'POST'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('Content: /match/get-head-post', $response->getContent());
    }
}

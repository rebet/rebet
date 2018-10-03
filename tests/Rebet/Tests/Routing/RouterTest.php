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
            Router::get('/get', function () {
                return 'Content: /get';
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
     * @expectedExceptionMessage Route POST /get not found.
     */
    public function test_routing_get_invalidMethod()
    {
        $response = Router::handle(Request::create('/get', 'POST'));
    }
}

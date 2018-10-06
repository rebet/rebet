<?php
namespace Rebet\Tests\Log;

use Rebet\Tests\RebetTestCase;
use Rebet\Tests\StderrCapture;

use Rebet\Routing\Router;

use Rebet\Common\Enum;
use Rebet\Common\NamespaceParser;
use Rebet\Common\System;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Foundation\App;
use Rebet\Http\Request;
use Rebet\Http\BasicResponse;

class RouterTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
        App::setSurface('web');
        Config::application([
            Router::class => [
                'middlewares!'   => [],
                'default_route!' => null,
                'fallback!'      => null,
            ]
        ]);

        Router::clear();
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

            Router::get('/parameter/requierd/{id}', function ($id) {
                return "Content: /parameter/requierd/{id} - {$id}";
            });

            Router::get('/parameter/option/{id?}', function ($id = 'default') {
                return "Content: /parameter/option/{id?} - {$id}";
            });
            
            Router::get('/parameter/between/{from}/to/{to}', function ($from, $to) {
                return "Content: /parameter/between/{from}/to/{to} - {$from}, {$to}";
            });

            Router::get('/parameter/between/invert/{from}/to/{to}', function ($to, $from) {
                return "Content: /parameter/between/invert/{from}/to/{to} - {$from}, {$to}";
            });

            Router::get('/parameter/where/{id}', function ($id) {
                return "Content: /parameter/where/{id} - {$id}";
            })->where('id', '/^[0-9]+$/');

            Router::get('/parameter/convert/int/{value}', function (int $value) {
                return "Content: /parameter/convert/int/{value} - {$value} ".(is_int($value) ? 'int' : 'not int');
            });

            Router::get('/parameter/convert/array/{value}', function (array $value) {
                return "Content: /parameter/convert/array/{value} - ".join('/', $value);
            });

            Router::get('/parameter/convert/date-time/{value}', function (DateTime $value) {
                return "Content: /parameter/convert/date-time/{value} - {$value->format('Y-m-d H:i:s.u')}";
            });

            Router::get('/parameter/convert/enum/{value}', function (RouterTest_Gender $value) {
                return "Content: /parameter/convert/enum/{value} - {$value}";
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
        $this->fail('Never execute.');
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
        $this->fail('Never execute.');
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
        $this->fail('Never execute.');
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
     * @expectedExceptionMessage Route: [GET|HEAD] /get where [] not found. Invalid method POST given.
     */
    public function test_routing_get_invalidMethod()
    {
        $response = Router::handle(Request::create('/get', 'POST'));
        $this->fail('Never execute.');
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
    
    public function test_routing_parameterRequierd()
    {
        $response = Router::handle(Request::create('/parameter/requierd/1'));
        $this->assertSame('Content: /parameter/requierd/{id} - 1', $response->getContent());
        
        $response = Router::handle(Request::create('/parameter/requierd/abc'));
        $this->assertSame('Content: /parameter/requierd/{id} - abc', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route GET /parameter/requierd not found.
     */
    public function test_routing_parameterRequierdNothing()
    {
        $response = Router::handle(Request::create('/parameter/requierd'));
        $this->fail('Never execute.');
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route GET /parameter/requierd/ not found.
     */
    public function test_routing_parameterRequierdNothing2()
    {
        $response = Router::handle(Request::create('/parameter/requierd/'));
        $this->fail('Never execute.');
    }

    public function test_routing_parameterOption()
    {
        $response = Router::handle(Request::create('/parameter/option/1'));
        $this->assertSame('Content: /parameter/option/{id?} - 1', $response->getContent());

        $response = Router::handle(Request::create('/parameter/option/abc'));
        $this->assertSame('Content: /parameter/option/{id?} - abc', $response->getContent());
        
        $response = Router::handle(Request::create('/parameter/option/'));
        $this->assertSame('Content: /parameter/option/{id?} - default', $response->getContent());

        $response = Router::handle(Request::create('/parameter/option'));
        $this->assertSame('Content: /parameter/option/{id?} - default', $response->getContent());

        $response = Router::handle(Request::create('/parameter/between/1/to/10'));
        $this->assertSame('Content: /parameter/between/{from}/to/{to} - 1, 10', $response->getContent());

        $response = Router::handle(Request::create('/parameter/between/invert/1/to/10'));
        $this->assertSame('Content: /parameter/between/invert/{from}/to/{to} - 1, 10', $response->getContent());

        $response = Router::handle(Request::create('/parameter/where/123'));
        $this->assertSame('Content: /parameter/where/{id} - 123', $response->getContent());

        $response = Router::handle(Request::create('/parameter/convert/int/123'));
        $this->assertSame('Content: /parameter/convert/int/{value} - 123 int', $response->getContent());

        $response = Router::handle(Request::create('/parameter/convert/array/123'));
        $this->assertSame('Content: /parameter/convert/array/{value} - 123', $response->getContent());

        $response = Router::handle(Request::create('/parameter/convert/array/1,2,3'));
        $this->assertSame('Content: /parameter/convert/array/{value} - 1/2/3', $response->getContent());

        $response = Router::handle(Request::create('/parameter/convert/date-time/20100123123456'));
        $this->assertSame('Content: /parameter/convert/date-time/{value} - 2010-01-23 12:34:56.000000', $response->getContent());

        $response = Router::handle(Request::create('/parameter/convert/enum/1'));
        $this->assertSame('Content: /parameter/convert/enum/{value} - 男性', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route: [GET|HEAD] /parameter/where/{id} where {"id":"\/^[0-9]+$\/"} not found. Routing parameter 'id' value 'abc' not match /^[0-9]+$/.
     */
    public function test_routing_parameterOptionInvalidWhere()
    {
        $response = Router::handle(Request::create('/parameter/where/abc'));
        $this->fail('Never execute.');
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route: [GET|HEAD] /parameter/convert/enum/{value} where [] not found. Routing parameter value(=3) can not convert to Rebet\Tests\Log\RouterTest_Gender.
     */
    public function test_routing_parameterOptionInvalidConvert()
    {
        $response = Router::handle(Request::create('/parameter/convert/enum/3'));
        $this->fail('Never execute.');
    }
}


class RouterTest_Gender extends Enum
{
    const MALE   = [1, '男性'];
    const FEMALE = [2, '女性'];
}

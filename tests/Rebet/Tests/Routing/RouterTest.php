<?php
namespace Rebet\Tests\Routing;

use org\bovigo\vfs\vfsStream;
use Rebet\Config\Config;

use Rebet\DateTime\DateTime;
use Rebet\Enum\Enum;
use Rebet\Foundation\App;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Routing\Annotation\AliasOnly;
use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\NotRouting;
use Rebet\Routing\Annotation\Where;
use Rebet\Routing\Controller;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Routing\Router;
use Rebet\Tests\Mock\DifferentNamespaceController;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class RouterTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
        App::setChannel('web');
        Config::application([
            App::class => [
                'namespace' => [
                    'controller' => '\\Rebet\\Tests\\Routing',
                ]
            ],
            Router::class => [
                'middlewares!' => [],
            ],
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => App::path('/resources/views/blade'),
                'cache_path' => 'vfs://root/cache',
            ],
        ]);

        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'cache' => [],
            ],
            $this->root
        );

        Router::clear();
        Router::rules('web')->routing(function () {
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

            Router::get('/parameter/convert/enum/{value}', function (Gender $value) {
                return "Content: /parameter/convert/enum/{value} - {$value}";
            });

            Router::match(['GET', 'HEAD', 'POST'], '/match/get-head-post', function () {
                return 'Content: /match/get-head-post';
            });

            Router::get('/json/array', function () {
                return [1, 2, 3];
            });

            Router::get('/json/jsonSerializable', function () {
                return DateTime::now();
            });

            Router::get('/renderable', function () {
                return View::of('welcome')->with(['name' => 'Samantha']);
            });
            
            Router::get('/method/private-call', 'RouterTestController@privateCall');
            Router::get('/method/private-call-accessible', 'RouterTestController@privateCall')->accessible(true);
            Router::get('/method/protected-call', 'RouterTestController@protectedCall');
            Router::get('/method/protected-call-accessible', 'RouterTestController@protectedCall')->accessible(true);
            Router::get('/method/public-call', 'RouterTestController@publicCall');
            Router::get('/method/with-param/{id}', 'RouterTestController@withParam');
            Router::get('/method/with-param/where/{id}', 'RouterTestController@withParam')->where('id', '/^[0-9]+$/');
            Router::get('/method/with-param/missmatch/{bad_name}', 'RouterTestController@withParam');
            Router::get('/method/with-optional-param/{id?}', 'RouterTestController@withOptionalParam');
            Router::get('/method/with-multi-param/{from}/to/{to}', 'RouterTestController@withMultiParam');
            Router::get('/method/with-multi-param/invert/{from}/to/{to}', 'RouterTestController@withMultiInvertParam');
            Router::get('/method/with-convert-enum-param/{gender}', 'RouterTestController@withConvertEnumParam');
            Router::get('/method/namespace/nest', 'Nest\\NestController@foo');
            Router::get('/method/namespace/different', 'Rebet\\Tests\\Mock\\DifferentNamespaceController@foo');
            
            Router::redirect('/redirect', '/destination');
            Router::redirect('/redirect/with-param/replace/{id}', '/destination/{id}');
            Router::redirect('/redirect/with-param/query/{id}', '/destination');
            Router::redirect('/redirect/with-optional-param/replace/{id?}', '/destination/{id}');
            Router::redirect('/redirect/with-multi-param/{from?}/{to?}', '/destination/{from}/{to}');
            Router::redirect('/redirect/with-multi-param/mix/{from}/{to}', '/destination/{from}');
            Router::redirect('/redirect/query', '/destination', ['page' => 1]);
            Router::redirect('/redirect/query/with-param/{id}', '/destination', ['page' => 1]);
            Router::redirect('/redirect/query/inline/with-param/{id}', '/destination?page=1');

            Router::view('/view', 'welcome', ['name' => 'Bob']);
            Router::view('/view/query', 'welcome');
            Router::view('/view/with-param/{name}', 'welcome');
            Router::view('/view/not-found/{name}', 'not-found');

            Router::controller('/controller/namespace/short', 'RouterTestController');
            Router::controller('/controller/namespace/nest', 'Nest\\NestController');
            Router::controller('/controller/namespace/different', DifferentNamespaceController::class);
            Router::controller('/controller/accessble', 'RouterTestController')->accessible(true);
            Router::controller('/controller/where', 'RouterTestController')->where('id', '/^[a-z]*$/');
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
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
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
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route GET /parameter/requierd not found.
     */
    public function test_routing_parameterRequierdNothing()
    {
        $response = Router::handle(Request::create('/parameter/requierd'));
        $this->fail('Never execute.');
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
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

        $response = Router::handle(Request::create('/parameter/convert/array/[1,2,3]'));
        $this->assertSame('Content: /parameter/convert/array/{value} - 1/2/3', $response->getContent());

        $response = Router::handle(Request::create('/parameter/convert/date-time/20100123123456'));
        $this->assertSame('Content: /parameter/convert/date-time/{value} - 2010-01-23 12:34:56.000000', $response->getContent());

        $response = Router::handle(Request::create('/parameter/convert/enum/1'));
        $this->assertSame('Content: /parameter/convert/enum/{value} - 男性', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: [GET|HEAD] /parameter/where/{id} where {"id":"\/^[0-9]+$\/"} not found. Routing parameter 'id' value 'abc' not match /^[0-9]+$/.
     */
    public function test_routing_parameterOptionInvalidWhere()
    {
        $response = Router::handle(Request::create('/parameter/where/abc'));
        $this->fail('Never execute.');
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: [GET|HEAD] /parameter/convert/enum/{value} where [] not found. Routing parameter value(=3) can not convert to Rebet\Tests\Mock\Gender.
     */
    public function test_routing_parameterOptionInvalidConvert()
    {
        $response = Router::handle(Request::create('/parameter/convert/enum/3'));
        $this->fail('Never execute.');
    }

    public function test_routing_jsonArray()
    {
        $response = Router::handle(Request::create('/json/array'));
        $this->assertSame('[1,2,3]', $response->getContent());
    }

    public function test_routing_jsonJsonSerializable()
    {
        $response = Router::handle(Request::create('/json/jsonSerializable'));
        $this->assertSame('"2010-10-20 10:20:30"', $response->getContent());
    }
    
    public function test_routing_renderable()
    {
        $response = Router::handle(Request::create('/renderable'));
        $this->assertSame('Hello, Samantha.', $response->getContent());
    }

    /**
     * @expectedException \ReflectionException
     */
    public function test_routing_methodPrivateCall()
    {
        $response = Router::handle(Request::create('/method/private-call'));
        $this->fail("Never execute.");
    }

    public function test_routing_methodPrivateCallAccessible()
    {
        $response = Router::handle(Request::create('/method/private-call-accessible'));
        $this->assertSame('Controller: privateCall', $response->getContent());
    }

    /**
     * @expectedException \ReflectionException
     */
    public function test_routing_methodProtectedCall()
    {
        $response = Router::handle(Request::create('/method/protected-call'));
        $this->fail("Never execute.");
    }

    public function test_routing_methodProtectedCallAccessible()
    {
        $response = Router::handle(Request::create('/method/protected-call-accessible'));
        $this->assertSame('Controller: protectedCall', $response->getContent());
    }
    
    public function test_routing_methodPublicCall()
    {
        $response = Router::handle(Request::create('/method/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route GET /method/with-param/ not found.
     */
    public function test_routing_methodWithParam()
    {
        $response = Router::handle(Request::create('/method/with-param/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());
        
        $response = Router::handle(Request::create('/method/with-param/'));
        $this->fail('Never execute.');
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: [GET|HEAD] /method/with-param/where/{id} where {"id":"\/^[0-9]+$\/"} not found. Routing parameter 'id' value 'abc' not match /^[0-9]+$/.
     */
    public function test_routing_methodWithParamWhere()
    {
        $response = Router::handle(Request::create('/method/with-param/where/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());
        
        $response = Router::handle(Request::create('/method/with-param/where/abc'));
        $this->fail('Never execute.');
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: [GET|HEAD] /method/with-param/missmatch/{bad_name} where [] not found. Routing parameter 'id' is requierd.
     */
    public function test_routing_methodWithMissmatchParam()
    {
        $response = Router::handle(Request::create('/method/with-param/missmatch/123'));
        $this->fail('Never execute.');
    }

    public function test_routing_methodWithOptionalParam()
    {
        $response = Router::handle(Request::create('/method/with-optional-param/123'));
        $this->assertSame('Controller: withOptionalParam - 123', $response->getContent());
        
        $response = Router::handle(Request::create('/method/with-optional-param/'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());

        $response = Router::handle(Request::create('/method/with-optional-param'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());
    }
    
    public function test_routing_methodWithMultiParam()
    {
        $response = Router::handle(Request::create('/method/with-multi-param/1/to/10'));
        $this->assertSame('Controller: withMultiParam - 1 to 10', $response->getContent());
    }
    
    public function test_routing_methodWithMultiInvertParam()
    {
        $response = Router::handle(Request::create('/method/with-multi-param/invert/1/to/10'));
        $this->assertSame('Controller: withMultiInvertParam - 1 to 10', $response->getContent());
    }
    
    public function test_routing_methodWithConvertEnumParam()
    {
        $response = Router::handle(Request::create('/method/with-convert-enum-param/1'));
        $this->assertSame('Controller: withConvertEnumParam - 男性', $response->getContent());

        $response = Router::handle(Request::create('/method/with-convert-enum-param/2'));
        $this->assertSame('Controller: withConvertEnumParam - 女性', $response->getContent());
    }

    public function test_routing_methodNamespaceNest()
    {
        $response = Router::handle(Request::create('/method/namespace/nest'));
        $this->assertSame('Nest: foo', $response->getContent());
    }

    public function test_routing_methodNamespaceDifferent()
    {
        $response = Router::handle(Request::create('/method/namespace/different'));
        $this->assertSame('Different: foo', $response->getContent());
    }

    public function test_routing_redirect()
    {
        $response = Router::handle(Request::create('/redirect'));
        $this->assertSame('/destination', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/with-param/replace/123'));
        $this->assertSame('/destination/123', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/with-param/query/123'));
        $this->assertSame('/destination?id=123', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/with-optional-param/replace/123'));
        $this->assertSame('/destination/123', $response->getTargetUrl());
        $response = Router::handle(Request::create('/redirect/with-optional-param/replace'));
        $this->assertSame('/destination', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/with-multi-param/a/z'));
        $this->assertSame('/destination/a/z', $response->getTargetUrl());
        $response = Router::handle(Request::create('/redirect/with-multi-param/a'));
        $this->assertSame('/destination/a', $response->getTargetUrl());
        $response = Router::handle(Request::create('/redirect/with-multi-param'));
        $this->assertSame('/destination', $response->getTargetUrl());
        $response = Router::handle(Request::create('/redirect/with-multi-param//z'));
        $this->assertSame('/destination/z', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/with-multi-param/mix/a/z'));
        $this->assertSame('/destination/a?to=z', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/query'));
        $this->assertSame('/destination?page=1', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/query/with-param/abc'));
        $this->assertSame('/destination?page=1&id=abc', $response->getTargetUrl());

        $response = Router::handle(Request::create('/redirect/query/inline/with-param/123'));
        $this->assertSame('/destination?page=1&id=123', $response->getTargetUrl());
    }

    public function test_routing_view()
    {
        $response = Router::handle(Request::create('/view'));
        $this->assertSame('Hello, Bob.', $response->getContent());

        $response = Router::handle(Request::create('/view/query', 'GET', ['name' => 'John']));
        $this->assertSame('Hello, John.', $response->getContent());

        $response = Router::handle(Request::create('/view/with-param/John'));
        $this->assertSame('Hello, John.', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage View route [not-found] not found. An exception occurred while processing the view.
     */
    public function test_routing_viewNotFound()
    {
        $response = Router::handle(Request::create('/view/not-found/John'));
        $this->fail('Never execute');
    }

    public function test_routing_controllerNamespaceShort()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/with-param/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/with-optional-param/abc'));
        $this->assertSame('Controller: withOptionalParam - abc', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/with-optional-param/'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/with-optional-param'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/with-multi-param/1/10'));
        $this->assertSame('Controller: withMultiParam - 1 to 10', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/with-multi-invert-param/1/10'));
        $this->assertSame('Controller: withMultiInvertParam - 10 to 1', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/with-convert-enum-param/1'));
        $this->assertSame('Controller: withConvertEnumParam - 男性', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-method-get'));
        $this->assertSame('Controller: annotationMethodGet', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-where/abc'));
        $this->assertSame('Controller: annotationWhere - abc', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-class-where/123'));
        $this->assertSame('Controller: annotationClassWhere - 123', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short/'));
        $this->assertSame('Controller: index', $response->getContent());

        $response = Router::handle(Request::create('/controller/namespace/short'));
        $this->assertSame('Controller: index', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationChannelApi not found. Routing channel 'web' not allowed or not annotated channel meta info.
     */
    public function test_routing_controllerAnnotationChannelReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-channel-api'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationMethodGet not found. Routing method 'POST' not allowed.
     */
    public function test_routing_controllerAnnotationMethodReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-method-get', 'POST'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationWhere not found. Routing parameter 'id' value '123' not match /^[a-zA-Z]+$/.
     */
    public function test_routing_controllerAnnotationWhereReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-where/123'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationClassWhere not found. Routing parameter 'user_id' value 'abc' not match /^[0-9]+$/.
     */
    public function test_routing_controllerAnnotationClassWhereReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-class-where/abc'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route not found : Action [ Rebet\Tests\Routing\RouterTestController::annotationNotRouting ] is not routing.
     */
    public function test_routing_controllerAnnotationNotRouting()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-not-routing'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route not found : Action [ Rebet\Tests\Routing\RouterTestController::undefinedAction ] not exists.
     */
    public function test_routing_controllerUndefinedAction()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/undefined-action'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route not found : Action [ Rebet\Tests\Routing\RouterTestController::privateCall ] not accessible.
     */
    public function test_routing_controllerPrivateCall()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/private-call'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route not found : Action [ Rebet\Tests\Routing\RouterTestController::protectedCall ] not accessible.
     */
    public function test_routing_controllerProtectedCall()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/protected-call'));
        $this->fail("Never Execute.");
    }

    public function test_routing_controllerNamespaceNest()
    {
        $response = Router::handle(Request::create('/controller/namespace/nest/foo'));
        $this->assertSame('Nest: foo', $response->getContent());
    }

    public function test_routing_controllerNamespaceDifferent()
    {
        $response = Router::handle(Request::create('/controller/namespace/different/foo'));
        $this->assertSame('Different: foo', $response->getContent());
    }

    public function test_routing_controllerAccessble()
    {
        $response = Router::handle(Request::create('/controller/accessble/private-call'));
        $this->assertSame('Controller: privateCall', $response->getContent());

        $response = Router::handle(Request::create('/controller/accessble/protected-call'));
        $this->assertSame('Controller: protectedCall', $response->getContent());
    }

    public function test_routing_controllerWhere()
    {
        $response = Router::handle(Request::create('/controller/where/with-param/abc'));
        $this->assertSame('Controller: withParam - abc', $response->getContent());

        $response = Router::handle(Request::create('/controller/where/annotation-where/ABC'));
        $this->assertSame('Controller: annotationWhere - ABC', $response->getContent());

        $response = Router::handle(Request::create('/controller/where/annotation-class-where/123'));
        $this->assertSame('Controller: annotationClassWhere - 123', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::withParam not found. Routing parameter 'id' value 'ABC' not match /^[a-z]*$/.
     */
    public function test_routing_controllerWhereReject()
    {
        $response = Router::handle(Request::create('/controller/where/with-param/ABC'));
        $this->fail("Never Execute.");
    }

    public function test_routing_defaultConventionalRoute()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class);
        });

        $response = Router::handle(Request::create('/router-test/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-param/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-optional-param/abc'));
        $this->assertSame('Controller: withOptionalParam - abc', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-optional-param/'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-optional-param'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-multi-param/1/10'));
        $this->assertSame('Controller: withMultiParam - 1 to 10', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-multi-invert-param/1/10'));
        $this->assertSame('Controller: withMultiInvertParam - 10 to 1', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-convert-enum-param/1'));
        $this->assertSame('Controller: withConvertEnumParam - 男性', $response->getContent());

        $response = Router::handle(Request::create('/router-test/annotation-method-get'));
        $this->assertSame('Controller: annotationMethodGet', $response->getContent());

        $response = Router::handle(Request::create('/router-test/annotation-where/abc'));
        $this->assertSame('Controller: annotationWhere - abc', $response->getContent());

        $response = Router::handle(Request::create('/router-test/annotation-class-where/123'));
        $this->assertSame('Controller: annotationClassWhere - 123', $response->getContent());

        $response = Router::handle(Request::create('/router-test/'));
        $this->assertSame('Controller: index', $response->getContent());

        $response = Router::handle(Request::create('/router-test'));
        $this->assertSame('Controller: index', $response->getContent());

        $response = Router::handle(Request::create('/'));
        $this->assertSame('Top: index', $response->getContent());
    }
    
    public function test_routing_defaultConventionalRouteWhere()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->where('id', '/^[a-z]+$/');
        });

        $response = Router::handle(Request::create('/top/with-param/abc'));
        $this->assertSame('Top: withParam - abc', $response->getContent());

        $response = Router::handle(Request::create('/router-test/with-param/abc'));
        $this->assertSame('Controller: withParam - abc', $response->getContent());

        $response = Router::handle(Request::create('/router-test/annotation-where/ABC'));
        $this->assertSame('Controller: annotationWhere - ABC', $response->getContent());

        $response = Router::handle(Request::create('/router-test/annotation-class-where/123'));
        $this->assertSame('Controller: annotationClassWhere - 123', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: \Rebet\Tests\Routing\TopController::withParam not found. Routing parameter 'id' value 'ABC' not match /^[a-z]*$/.
     */
    public function test_routing_defaultConventionalRouteWhereRejectTop()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->where('id', '/^[a-z]*$/');
        });

        $response = Router::handle(Request::create('/top/with-param/ABC'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route: \Rebet\Tests\Routing\RouterTestController::withParam not found. Routing parameter 'id' value 'ABC' not match /^[a-z]*$/.
     */
    public function test_routing_defaultConventionalRouteWhereRejectRouterTest()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->where('id', '/^[a-z]*$/');
        });

        $response = Router::handle(Request::create('/router-test/with-param/ABC'));
        $this->fail("Never Execute.");
    }
    
    public function test_routing_defaultConventionalRouteAccessible()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->accessible(true);
        });
        
        $response = Router::handle(Request::create('/router-test/private-call'));
        $this->assertSame('Controller: privateCall', $response->getContent());

        $response = Router::handle(Request::create('/router-test/protected-call'));
        $this->assertSame('Controller: protectedCall', $response->getContent());
    }

    /**
     * @expectedException \Rebet\Routing\Exception\RouteNotFoundException
     * @expectedExceptionMessage Route not found : Action [ \Rebet\Tests\Routing\RouterTestController::annotationAliasOnly ] accespt only alias access.
     */
    public function test_routing_defaultConventionalRouteAliasOnly()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class);
        });

        $response = Router::handle(Request::create('/router-test/annotation-alias-only'));
        $this->fail("Never Execute.");
    }

    public function test_routing_defaultConventionalRouteAlias()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->aliases([
                '/alias'       => '/router-test/annotation-alias-only',
                '/param'       => '/router-test/with-param',
                '/one-to'      => '/router-test/with-multi-param/1',
                '/annotation/' => '/router-test/annotation-',
                '/foo/bar'     => '/top',
            ]);
        });

        $response = Router::handle(Request::create('/alias'));
        $this->assertSame('Controller: annotationAliasOnly', $response->getContent());
        $this->assertSame('/alias', Router::current()->getAliasName());

        $response = Router::handle(Request::create('/param/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());
        $this->assertSame('/param', Router::current()->getAliasName());

        $response = Router::handle(Request::create('/one-to/10'));
        $this->assertSame('Controller: withMultiParam - 1 to 10', $response->getContent());
        $this->assertSame('/one-to', Router::current()->getAliasName());

        $response = Router::handle(Request::create('/foo/bar'));
        $this->assertSame('Top: index', $response->getContent());
        $this->assertSame('/foo/bar', Router::current()->getAliasName());

        $response = Router::handle(Request::create('/foo/bar/with-param/123'));
        $this->assertSame('Top: withParam - 123', $response->getContent());
        $this->assertSame('/foo/bar', Router::current()->getAliasName());

        $response = Router::handle(Request::create('/annotation/where/ABC'));
        $this->assertSame('Controller: annotationWhere - ABC', $response->getContent());
        $this->assertSame('/annotation/', Router::current()->getAliasName());

        $response = Router::handle(Request::create('/annotation/class-where/123'));
        $this->assertSame('Controller: annotationClassWhere - 123', $response->getContent());
        $this->assertSame('/annotation/', Router::current()->getAliasName());
    }

    public function test_routing_prefix()
    {
        Router::clear();
        Router::rules('web')->prefix('/prefix')->routing(function () {
            Router::get('/get', function () { return 'Content: /prefix/get'; });
            Router::get('/method/public-call', 'RouterTestController@publicCall');
            Router::controller('/controller/namespace/short', 'RouterTestController');
            Router::default(ConventionalRoute::class);
        })->fallback(function (Request $request, \Throwable $e) {
            return Responder::toResponse('fallback prefix');
        });

        Router::rules('web')->routing(function () {
            Router::get('/get', function () { return 'Content: /get'; });
        })->fallback(function (Request $request, \Throwable $e) {
            return Responder::toResponse('fallback');
        });

        $response = Router::handle(Request::create('/get-none'));
        $this->assertSame('fallback', $response->getContent());
        $response = Router::handle(Request::create('/get'));
        $this->assertSame('Content: /get', $response->getContent());
        $response = Router::handle(Request::create('/prefix/get-non'));
        $this->assertSame('fallback prefix', $response->getContent());
        $response = Router::handle(Request::create('/prefix/get'));
        $this->assertSame('Content: /prefix/get', $response->getContent());

        $response = Router::handle(Request::create('/prefix/method/public-call-non'));
        $this->assertSame('fallback prefix', $response->getContent());
        $response = Router::handle(Request::create('/prefix/method/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());

        $response = Router::handle(Request::create('/prefix/controller/namespace/short/public-call-non'));
        $this->assertSame('fallback prefix', $response->getContent());
        $response = Router::handle(Request::create('/prefix/controller/namespace/short/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());

        $response = Router::handle(Request::create('/prefix/router-test/public-call-non'));
        $this->assertSame('fallback prefix', $response->getContent());
        $response = Router::handle(Request::create('/prefix/router-test/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());
    }
}

/**
 * @Channel("web")
 * @Where({"user_id": "/^[0-9]+$/"})
 */
class RouterTestController extends Controller
{
    public function index()
    {
        return 'Controller: index';
    }

    private function privateCall()
    {
        return 'Controller: privateCall';
    }

    protected function protectedCall()
    {
        return 'Controller: protectedCall';
    }

    public function publicCall()
    {
        return 'Controller: publicCall';
    }

    public function withParam($id)
    {
        return "Controller: withParam - {$id}";
    }

    public function withOptionalParam($id = 'default')
    {
        return "Controller: withOptionalParam - {$id}";
    }
    
    public function withMultiParam($from, $to)
    {
        return "Controller: withMultiParam - {$from} to {$to}";
    }

    public function withMultiInvertParam($to, $from)
    {
        return "Controller: withMultiInvertParam - {$from} to {$to}";
    }

    public function withConvertEnumParam(Gender $gender)
    {
        return "Controller: withConvertEnumParam - {$gender}";
    }
    
    /**
     * @Channel("api")
     */
    public function annotationChannelApi()
    {
        return 'Controller: annotationChannelApi';
    }

    /**
     * @Method("GET")
     */
    public function annotationMethodGet()
    {
        return 'Controller: annotationMethodGet';
    }

    /**
     * @Where({"id": "/^[a-zA-Z]+$/"})
     */
    public function annotationWhere($id)
    {
        return "Controller: annotationWhere - {$id}";
    }

    public function annotationClassWhere($user_id)
    {
        return "Controller: annotationClassWhere - {$user_id}";
    }

    /**
     * @NotRouting
     */
    public function annotationNotRouting()
    {
        return "Controller: annotationNotRouting";
    }

    /**
     * @AliasOnly
     */
    public function annotationAliasOnly()
    {
        return "Controller: annotationAliasOnly";
    }
}

/**
 * @Channel("web")
 */
class TopController extends Controller
{
    public function index()
    {
        return 'Top: index';
    }
    
    public function withParam($id)
    {
        return "Top: withParam - {$id}";
    }
}

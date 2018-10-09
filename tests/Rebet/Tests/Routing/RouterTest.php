<?php
namespace Rebet\Tests\Routing;

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
use Rebet\Routing\Controller;
use Rebet\Routing\ControllerRoute;
use Rebet\Routing\Annotation\Surface;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\Where;
use Rebet\Tests\Different\DifferentController;
use Rebet\Tests\Mock\DifferentNamespaceController;
use Rebet\Tests\Mock\Gender;

class RouterTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
        App::setSurface('web');
        Config::application([
            App::class => [
                'namespace' => [
                    'controller' => '\Rebet\Tests\Routing',
                ]
            ],
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

            Router::get('/parameter/convert/enum/{value}', function (Gender $value) {
                return "Content: /parameter/convert/enum/{value} - {$value}";
            });

            Router::match(['GET', 'HEAD', 'POST'], '/match/get-head-post', function () {
                return 'Content: /match/get-head-post';
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
            
            Router::controller('/controller/namespace/short', 'RouterTestController');
            Router::controller('/controller/namespace/nest', 'Nest\\NestController');
            Router::controller('/controller/namespace/different', DifferentNamespaceController::class);
            Router::controller('/controller/accessble', 'RouterTestController')->accessible(true);
            Router::controller('/controller/where', 'RouterTestController')->where('id', '/^[a-z]*$/');

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
     * @expectedExceptionMessage Route: [GET|HEAD] /parameter/convert/enum/{value} where [] not found. Routing parameter value(=3) can not convert to Rebet\Tests\Mock\Gender.
     */
    public function test_routing_parameterOptionInvalidConvert()
    {
        $response = Router::handle(Request::create('/parameter/convert/enum/3'));
        $this->fail('Never execute.');
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
     * @expectedException \Rebet\Routing\RouteNotFoundException
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
     * @expectedException \Rebet\Routing\RouteNotFoundException
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
     * @expectedException \Rebet\Routing\RouteNotFoundException
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
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationSurfaceApi not found. Routing surface 'web' not allowed or not annotated surface meta info.
     */
    public function test_routing_controllerAnnotationSurfaceReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-surface-api'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationMethodGet not found. Routing method 'POST' not allowed.
     */
    public function test_routing_controllerAnnotationMethodReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-method-get', 'POST'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationWhere not found. Routing parameter 'id' value '123' not match /^[a-zA-Z]+$/.
     */
    public function test_routing_controllerAnnotationWhereReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-where/123'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::annotationClassWhere not found. Routing parameter 'user_id' value 'abc' not match /^[0-9]+$/.
     */
    public function test_routing_controllerAnnotationClassWhereReject()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/annotation-class-where/abc'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route not found : Action [ Rebet\Tests\Routing\RouterTestController::undefinedAction ] not exists.
     */
    public function test_routing_controllerUndefinedAction()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/undefined-action'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route not found : Action [ Rebet\Tests\Routing\RouterTestController::privateCall ] not accessible.
     */
    public function test_routing_controllerPrivateCall()
    {
        $response = Router::handle(Request::create('/controller/namespace/short/private-call'));
        $this->fail("Never Execute.");
    }

    /**
     * @expectedException \Rebet\Routing\RouteNotFoundException
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
     * @expectedException \Rebet\Routing\RouteNotFoundException
     * @expectedExceptionMessage Route: Rebet\Tests\Routing\RouterTestController::withParam not found. Routing parameter 'id' value 'ABC' not match /^[a-z]*$/.
     */
    public function test_routing_controllerWhereReject()
    {
        $response = Router::handle(Request::create('/controller/where/with-param/ABC'));
        $this->fail("Never Execute.");
    }
}

/**
 * @Surface("web")
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
     * @Surface("api")
     */
    public function annotationSurfaceApi()
    {
        return 'Controller: annotationSurfaceApi';
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
}

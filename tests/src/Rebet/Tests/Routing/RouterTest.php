<?php
namespace Rebet\Tests\Routing;

use App\Different\DifferentNamespaceController;
use App\Enum\Gender;
use Rebet\Application\App;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Middleware\Routing\EmptyStringToNull;
use Rebet\Middleware\Routing\TrimStrings;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Routing\Router;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class RouterTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        App::setLocale('ja');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
        Config::application([
            Router::class => [
                'middlewares='             => [],
                'default_fallback_handler' => function (Request $request, \Throwable $e) {
                    throw $e;
                }
            ],
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::structure()->views('/blade')],
                'cache_path' => 'vfs://root/cache',
            ],
            Log::class => [
                'channels' => [
                    'web' => [
                        'driver' => StderrDriver::class,
                        'name'   => 'web',
                        'level'  => LogLevel::DEBUG,
                    ],
                ],
            ],
        ]);

        $this->vfs([
            'cache' => [],
        ]);

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

            Router::get('/method/private-call', 'TestController::privateCall');
            Router::get('/method/private-call-accessible', 'TestController::privateCall')->accessible(true);
            Router::get('/method/protected-call', 'TestController::protectedCall');
            Router::get('/method/protected-call-accessible', 'TestController::protectedCall')->accessible(true);
            Router::get('/method/public-call', 'TestController::publicCall');
            Router::get('/method/with-param/{id}', 'TestController::withParam');
            Router::get('/method/with-param/where/{id}', 'TestController::withParam')->where('id', '/^[0-9]+$/');
            Router::get('/method/with-param/missmatch/{bad_name}', 'TestController::withParam');
            Router::get('/method/with-optional-param/{id?}', 'TestController::withOptionalParam');
            Router::get('/method/with-multi-param/{from}/to/{to}', 'TestController::withMultiParam');
            Router::get('/method/with-multi-param/invert/{from}/to/{to}', 'TestController::withMultiInvertParam');
            Router::get('/method/with-convert-enum-param/{gender}', 'TestController::withConvertEnumParam');
            Router::get('/method/namespace/nest', 'Nest\\NestController::foo');
            Router::get('/method/namespace/different', 'App\\Different\\DifferentNamespaceController::foo');

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

            Router::controller('/controller/namespace/short', 'TestController');
            Router::controller('/controller/namespace/nest', 'Nest\\NestController');
            Router::controller('/controller/namespace/different', DifferentNamespaceController::class);
            Router::controller('/controller/accessble', 'TestController')->accessible(true);
            Router::controller('/controller/where', 'TestController')->where('id', '/^[a-z]*$/');
        });
    }

    public function test_getAndSetCurrentChannel()
    {
        $this->assertSame('web', Router::getCurrentChannel());
        Router::setCurrentChannel('api');
        $this->assertSame('api', Router::getCurrentChannel());
        $this->inject(App::class, ['kernel.channel' => 'console']);
        $this->assertSame('api', Router::getCurrentChannel());
    }

    public function test_clear()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route GET / not found.");

        $response = Router::handle(Request::create('/'));
        $this->assertSame(200, $response->getStatusCode());

        Router::clear();

        $response = Router::handle(Request::create('/'));
    }

    public function test_invalidRuleDefine_match()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Routing rules are defined without Router::rules(). You should wrap rules by Router::rules().");

        Router::match('GET', '/get', function () {
            return 'Content: /get';
        });
    }

    public function test_invalidRuleDefine_default()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Routing default rules are defined without Router::rules(). You should wrap rules by Router::rules().");

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

    public function test_routing_get_invalidMethod()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: [GET|HEAD] /get not found. Invalid method POST given.");

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

    public function test_routing_invalidMatch()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid action type for declarative routing. Action should be string of 'Class::method' or callable.");

        Router::rules('web')->routing(function () {
            Router::match(['GET', 'HEAD', 'POST'], '/match/invlid-action', null);
        });
    }

    public function test_routing_parameterRequierd()
    {
        $response = Router::handle(Request::create('/parameter/requierd/1'));
        $this->assertSame('Content: /parameter/requierd/{id} - 1', $response->getContent());

        $response = Router::handle(Request::create('/parameter/requierd/abc'));
        $this->assertSame('Content: /parameter/requierd/{id} - abc', $response->getContent());
    }

    public function test_routing_parameterRequierdNothing()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route GET /parameter/requierd not found.");

        $response = Router::handle(Request::create('/parameter/requierd'));
    }

    public function test_routing_parameterRequierdNothing2()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route GET /parameter/requierd/ not found.");

        $response = Router::handle(Request::create('/parameter/requierd/'));
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

    public function test_routing_parameterOptionInvalidWhere()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: [GET|HEAD] /parameter/where/{id} where {\"id\":\"\/^[0-9]+$\/\"} not found. Routing parameter 'id' value 'abc' not match /^[0-9]+$/.");

        $response = Router::handle(Request::create('/parameter/where/abc'));
    }

    public function test_routing_parameterOptionInvalidConvert()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: [GET|HEAD] /parameter/convert/enum/{value} not found. Routing parameter value(=3) can not convert to App\Enum\Gender.");

        $response = Router::handle(Request::create('/parameter/convert/enum/3'));
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

    public function test_routing_methodPrivateCall()
    {
        $this->expectException(\ReflectionException::class);

        $response = Router::handle(Request::create('/method/private-call'));
    }

    public function test_routing_methodPrivateCallAccessible()
    {
        $response = Router::handle(Request::create('/method/private-call-accessible'));
        $this->assertSame('Controller: privateCall', $response->getContent());
    }

    public function test_routing_methodProtectedCall()
    {
        $this->expectException(\ReflectionException::class);

        $response = Router::handle(Request::create('/method/protected-call'));
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

    public function test_routing_methodWithParam()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route GET /method/with-param/ not found.");

        $response = Router::handle(Request::create('/method/with-param/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());

        $response = Router::handle(Request::create('/method/with-param/'));
    }

    public function test_routing_methodWithParamWhere()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: [GET|HEAD] /method/with-param/where/{id} where {\"id\":\"\/^[0-9]+$\/\"} not found. Routing parameter 'id' value 'abc' not match /^[0-9]+$/.");

        $response = Router::handle(Request::create('/method/with-param/where/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());

        $response = Router::handle(Request::create('/method/with-param/where/abc'));
    }

    public function test_routing_methodWithMissmatchParam()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: [GET|HEAD] /method/with-param/missmatch/{bad_name} not found. Routing parameter 'id' is requierd.");

        $response = Router::handle(Request::create('/method/with-param/missmatch/123'));
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

    public function test_routing_viewNotFound()
    {
        $this->expectException(RouteNotFoundException::class);

        $response = Router::handle(Request::create('/view/not-found/John'));
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

    public function test_routing_controllerAnnotationChannelReject()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::annotationChannelApi not found. Routing channel 'web' not allowed or not annotated channel meta info.");

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-channel-api'));
    }

    public function test_routing_controllerAnnotationMethodReject()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::annotationMethodGet not found. Routing method 'POST' not allowed.");

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-method-get', 'POST'));
    }

    public function test_routing_controllerAnnotationWhereReject()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::annotationWhere not found. Routing parameter 'id' value '123' not match /^[a-zA-Z]+$/.");

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-where/123'));
    }

    public function test_routing_controllerAnnotationClassWhereReject()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::annotationClassWhere not found. Routing parameter 'user_id' value 'abc' not match /^[0-9]+$/.");

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-class-where/abc'));
    }

    public function test_routing_controllerAnnotationNotRouting()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ App\Controller\TestController::annotationNotRouting ] is not routing.");

        $response = Router::handle(Request::create('/controller/namespace/short/annotation-not-routing'));
    }

    public function test_routing_controllerUndefinedAction()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ App\Controller\TestController::undefinedAction ] not exists.");

        $response = Router::handle(Request::create('/controller/namespace/short/undefined-action'));
    }

    public function test_routing_controllerPrivateCall()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ App\Controller\TestController::privateCall ] not accessible.");

        $response = Router::handle(Request::create('/controller/namespace/short/private-call'));
    }

    public function test_routing_controllerProtectedCall()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ App\Controller\TestController::protectedCall ] not accessible.");

        $response = Router::handle(Request::create('/controller/namespace/short/protected-call'));
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

    public function test_routing_controllerWhereReject()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::withParam not found. Routing parameter 'id' value 'ABC' not match /^[a-z]*$/.");

        $response = Router::handle(Request::create('/controller/where/with-param/ABC'));
    }

    public function test_routing_defaultConventionalRoute()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class);
        });

        $response = Router::handle(Request::create('/test/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());

        $response = Router::handle(Request::create('/test/with-param/123'));
        $this->assertSame('Controller: withParam - 123', $response->getContent());

        $response = Router::handle(Request::create('/test/with-optional-param/abc'));
        $this->assertSame('Controller: withOptionalParam - abc', $response->getContent());

        $response = Router::handle(Request::create('/test/with-optional-param/'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());

        $response = Router::handle(Request::create('/test/with-optional-param'));
        $this->assertSame('Controller: withOptionalParam - default', $response->getContent());

        $response = Router::handle(Request::create('/test/with-multi-param/1/10'));
        $this->assertSame('Controller: withMultiParam - 1 to 10', $response->getContent());

        $response = Router::handle(Request::create('/test/with-multi-invert-param/1/10'));
        $this->assertSame('Controller: withMultiInvertParam - 10 to 1', $response->getContent());

        $response = Router::handle(Request::create('/test/with-convert-enum-param/1'));
        $this->assertSame('Controller: withConvertEnumParam - 男性', $response->getContent());

        $response = Router::handle(Request::create('/test/annotation-method-get'));
        $this->assertSame('Controller: annotationMethodGet', $response->getContent());

        $response = Router::handle(Request::create('/test/annotation-where/abc'));
        $this->assertSame('Controller: annotationWhere - abc', $response->getContent());

        $response = Router::handle(Request::create('/test/annotation-class-where/123'));
        $this->assertSame('Controller: annotationClassWhere - 123', $response->getContent());

        $response = Router::handle(Request::create('/test/'));
        $this->assertSame('Controller: index', $response->getContent());

        $response = Router::handle(Request::create('/test'));
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

        $response = Router::handle(Request::create('/test/with-param/abc'));
        $this->assertSame('Controller: withParam - abc', $response->getContent());

        $response = Router::handle(Request::create('/test/annotation-where/ABC'));
        $this->assertSame('Controller: annotationWhere - ABC', $response->getContent());

        $response = Router::handle(Request::create('/test/annotation-class-where/123'));
        $this->assertSame('Controller: annotationClassWhere - 123', $response->getContent());
    }

    public function test_routing_defaultConventionalRouteNotFound()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Controller [ App\Controller\InvalidController ] can not instantiate.");

        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class);
        });

        $response = Router::handle(Request::create('/invalid'));
    }

    public function test_routing_defaultConventionalRouteWhereRejectTop()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TopController::withParam not found. Routing parameter 'id' value 'ABC' not match /^[a-z]*$/.");

        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->where('id', '/^[a-z]*$/');
        });

        $response = Router::handle(Request::create('/top/with-param/ABC'));
    }

    public function test_routing_defaultConventionalRouteWhereRejectRouterTest()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route: App\Controller\TestController::withParam not found. Routing parameter 'id' value 'ABC' not match /^[a-z]*$/.");

        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->where('id', '/^[a-z]*$/');
        });

        $response = Router::handle(Request::create('/test/with-param/ABC'));
    }

    public function test_routing_defaultConventionalRouteAccessible()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->accessible(true);
        });

        $response = Router::handle(Request::create('/test/private-call'));
        $this->assertSame('Controller: privateCall', $response->getContent());

        $response = Router::handle(Request::create('/test/protected-call'));
        $this->assertSame('Controller: protectedCall', $response->getContent());
    }

    public function test_routing_defaultConventionalRouteAliasOnly()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("Route not found : Action [ App\Controller\TestController::annotationAliasOnly ] accespt only alias access.");

        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class);
        });

        $response = Router::handle(Request::create('/test/annotation-alias-only'));
    }

    public function test_routing_defaultConventionalRouteAlias()
    {
        Router::clear();
        Router::rules('web')->routing(function () {
            Router::default(ConventionalRoute::class)->aliases([
                '/alias'       => '/test/annotation-alias-only',
                '/param'       => '/test/with-param',
                '/one-to'      => '/test/with-multi-param/1',
                '/annotation/' => '/test/annotation-',
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
            Router::get('/method/public-call', 'TestController::publicCall');
            Router::controller('/controller/namespace/short', 'TestController');
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

        $response = Router::handle(Request::create('/prefix/test/public-call-non'));
        $this->assertSame('fallback prefix', $response->getContent());
        $response = Router::handle(Request::create('/prefix/test/public-call'));
        $this->assertSame('Controller: publicCall', $response->getContent());

        Router::clear();
        Config::application([
            Router::class => [
                'default_fallback_handler' => function (Request $request, \Throwable $e) {
                    return Responder::toResponse('fallback default');
                }
            ]
        ]);

        $response = Router::handle(Request::create('/'));
        $this->assertSame('fallback default', $response->getContent());
    }

    public function test_terminate()
    {
        Router::clear();
        $middleware = new RouterTest_TerminatableMiddleware();
        Router::rules('web')->routing(function () use ($middleware) {
            Router::default(ConventionalRoute::class)->middlewares($middleware);
        });
        $request    = Request::create('/test/index');
        $response   = Router::handle($request);
        $controller = Reflector::get($request->route, 'controller', null, true);
        $this->assertSame(0, $controller->terminate_count);
        $this->assertSame(0, $middleware->terminate_count);
        Router::terminate($request, $response);
        $this->assertSame(1, $controller->terminate_count);
        $this->assertSame(1, $middleware->terminate_count);
    }

    public function test_current()
    {
        $this->assertNull(Router::current());
        $request  = Request::create('/get');
        $response = Router::handle($request);
        $route    = Router::current();
        $this->assertSame($request->route, $route);
    }

    public function test_getPrefixFrom()
    {
        Router::rules('web')->prefix('/foo');
        Router::rules('web')->prefix('/bar');
        $this->assertSame(null, Router::getPrefixFrom('/'));
        $this->assertSame('/foo', Router::getPrefixFrom('/foo'));
        $this->assertSame('/foo', Router::getPrefixFrom('/foo/'));
        $this->assertSame('/foo', Router::getPrefixFrom('/foo/baz'));
        $this->assertSame(null, Router::getPrefixFrom('/foobar'));
        $this->assertSame(null, Router::getPrefixFrom('/baz'));
        $this->assertSame('/bar', Router::getPrefixFrom('/bar'));
    }

    public function test_activatePrefix()
    {
        $this->assertSame(null, Router::getPrefixFrom('/foo/bar'));
        $this->assertSame('/foo', Router::activatePrefix('/foo'));
        $this->assertSame('/foo', Router::getPrefixFrom('/foo/bar'));
    }

    public function test_rules()
    {
        $this->assertSame('web', Reflector::get(Router::rules('web'), 'channel', null, true));
    }

    public function test_prefix()
    {
        $this->assertSame('/prefix', Reflector::get(Router::rules('web')->prefix('/prefix'), 'prefix', null, true));
    }

    public function test_middlewares()
    {
        $this->assertSame([EmptyStringToNull::class, TrimStrings::class], Reflector::get(Router::rules('web')->middlewares(EmptyStringToNull::class, TrimStrings::class), 'middlewares', null, true));
    }

    public function test_roles()
    {
        $this->assertSame(['user', 'admin'], Reflector::get(Router::rules('web')->roles('user', 'admin'), 'roles', null, true));
    }

    public function test_guard()
    {
        $this->assertSame('web', Reflector::get(Router::rules('web')->guard('web'), 'guard', null, true));
    }
}

class RouterTest_TerminatableMiddleware
{
    public $terminate_count = 0;

    public function handle(Request $request, \Closure $next) : Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        $this->terminate_count++;
    }
}

<?php
namespace Rebet\Tests\Application\Error;

use Rebet\Application\App;
use Rebet\Application\Error\ExceptionHandler;
use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Config\Config;
use Rebet\Config\Exception\ConfigNotDefineException;
use Rebet\Http\Exception\FallbackRedirectException;
use Rebet\Http\Exception\HttpException;
use Rebet\Http\Exception\TokenMismatchException;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Log\Log;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Twig\Twig;
use Rebet\View\View;

class ExceptionHandlerTest extends RebetTestCase
{
    /** @var ExceptionHandler */
    public $handler;

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
                'view_path'  => [App::structure()->views('/blade')],
                'cache_path' => 'vfs://root/cache',
            ],
        ]);

        $this->handler             = new class extends ExceptionHandler {
            public $reported_count = 0;

            protected function reportHttp(Request $request, ?Response $response, \Throwable $e) : void
            {
                $this->reported_count++;
            }
        };
    }

    public function test_handle()
    {
        $reported_count = 0;
        $this->assertSame($reported_count++, $this->handler->reported_count);

        $request  = $this->createJsonRequestMock('/');
        $response = $this->handler->handle($request, null, new \Exception('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertSame([
            'status' => 500,
            'title'  => 'Internal Server Error',
            'type'   => 'about:blank',
            'detail' => 'Detail message',
        ], $response->getProblem());

        $request  = $this->createJsonRequestMock('/');
        $response = $this->handler->handle($request, null, RouteNotFoundException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertSame([
            'status' => 404,
            'title'  => '指定のページが見つかりません',
            'type'   => 'about:blank',
            'detail' => 'ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。',
        ], $response->getProblem());


        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, FallbackRedirectException::by('Detail message')->to('/'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertContains('/', $response->getTargetUrl());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, HttpException::by(403));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">403</span>Forbidden</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, HttpException::by(403)->title('Custom Title'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertContains('<h2 class="title">Custom Title</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, AuthenticateException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">403</span>Forbidden</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, RouteNotFoundException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertContains('<h2 class="title">指定のページが見つかりません</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, TokenMismatchException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertContains('<h2 class="title">指定のページが見つかりません</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, HttpException::by(400)->title('Use errors custom view template'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertContains('400 Bad Request Custom Error View Template', $response->getContent());
        $this->assertContains('Use errors custom view template', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, new \Exception());
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">500</span>Internal Server Error</h2>', $response->getContent());

        Config::application([
            View::class => [
                'engine' => Twig::class,
            ],
            Twig::class => [
                'template_dir' => [App::structure()->views('/twig')],
                'options'      => [
                    // 'cache' => 'vfs://root/cache',
                ],
            ],
        ]);

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, null, new \Exception());
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertContains('Default Custom Error View Template (Only Twig)', $response->getContent());
        $this->assertContains('title     : Internal Server Error', $response->getContent());
    }

    public function test___invoke()
    {
        $request  = $this->createRequestMock('/');
        $response = $this->handler->__invoke($request, new \Exception());
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">500</span>Internal Server Error</h2>', $response->getContent());
    }

    public function test___construct()
    {
        $this->assertInstanceOf(ExceptionHandler::class, new ExceptionHandler());
    }

    public function test_handle_web()
    {
        App::setLocale('de', 'de');
        $request = $this->createRequestMock('/');
        $handler = new ExceptionHandler();

        $response = $handler->handle($request, null, AuthenticateException::by('Authentication failed'));
        $this->assertContains('<span class="status">403</span>Forbidden', $response->getContent());
        $this->assertContains('Authentication failed', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 403 Forbidden occurred.', $log);
        $this->assertContains('Rebet\Auth\Exception\AuthenticateException: Authentication failed in', $log);

        $response = $handler->handle($request, null, RouteNotFoundException::by('Route not found'));
        $this->assertContains('<span class="status">404</span>Not Found', $response->getContent());
        $this->assertContains('Route not found', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 404 Not Found occurred.', $log);
        $this->assertContains('Rebet\Routing\Exception\RouteNotFoundException: Route not found in', $log);

        $response = $handler->handle($request, null, ConfigNotDefineException::by('unit test'));
        $this->assertContains('<span class="status">500</span>Internal Server Error', $response->getContent());
        $this->assertContains('unit test', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasErrorRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 500 Internal Server Error occurred.', $log);
        $this->assertContains('Rebet\Config\Exception\ConfigNotDefineException: unit test in', $log);
    }

    public function test_handle_json()
    {
        App::setLocale('en');
        $request = $this->createJsonRequestMock('/');
        $handler = new ExceptionHandler();

        $response = $handler->handle($request, null, AuthenticateException::by('Authentication failed'));
        $this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
        $this->assertSame('{"status":403,"title":"Forbidden","type":"about:blank","detail":"Authentication failed"}', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 403 Forbidden occurred.', $log);
        $this->assertContains('Rebet\Auth\Exception\AuthenticateException: Authentication failed in', $log);

        $response = $handler->handle($request, null, RouteNotFoundException::by('Route not found'));
        $this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
        $this->assertSame('{"status":404,"title":"Custom Not Found","type":"about:blank","detail":"The page could not be found. The specified URL is incorrect, or the page may have already been deleted \/ moved."}', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 403 Forbidden occurred.', $log);
        $this->assertContains('Rebet\Auth\Exception\AuthenticateException: Authentication failed in', $log);

        $response = $handler->handle($request, null, ConfigNotDefineException::by('unit test'));
        $this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
        $this->assertSame('{"status":500,"title":"Internal Server Error","type":"about:blank","detail":"unit test"}', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasErrorRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 500 Internal Server Error occurred.', $log);
        $this->assertContains('Rebet\Config\Exception\ConfigNotDefineException: unit test in', $log);
    }
}

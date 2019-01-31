<?php
namespace Rebet\Tests\Foundation\Routing;

use org\bovigo\vfs\vfsStream;
use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Config\Config;
use Rebet\Config\Exception\ConfigNotDefineException;
use Rebet\Foundation\App;
use Rebet\Foundation\Routing\BasicFallbackHandler;
use Rebet\Log\Handler\StderrHandler;
use Rebet\Log\LogLevel;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class BasicFallbackHandlerTest extends RebetTestCase
{
    private $root;

    public function setUp()
    {
        parent::setUp();
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'cache' => [],
            ],
            $this->root
        );
        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::path('/resources/views/blade')],
                'cache_path' => 'vfs://root/cache',
            ],
        ]);
    }

    public function test___construct()
    {
        $this->assertInstanceOf(BasicFallbackHandler::class, new BasicFallbackHandler());
    }

    public function test_handle_web()
    {
        $request = $this->createRequestMock('/');
        $handler = new BasicFallbackHandler();
        Config::application([
            StderrHandler::class => [
                'log_level' => LogLevel::TRACE(),
            ],
        ]);

        $this->assertContainsStderr(
            [
                '[TRACE]',
                'HTTP 403 Forbidden occurred.',
                'Rebet\Auth\Exception\AuthenticateException: Authentication failed in',
            ],
            function () use ($handler, $request) {
                $response = $handler->handle($request, AuthenticateException::by('Authentication failed'));
                $this->assertContains('<span class="status">403</span>Forbidden', $response->getContent());
                $this->assertContains('Authentication failed', $response->getContent());
            }
        );

        $this->assertContainsStderr(
            [
                '[TRACE]',
                'HTTP 404 Not Found occurred.',
                'Rebet\Routing\Exception\RouteNotFoundException: Route not found in',
            ],
            function () use ($handler, $request) {
                $response = $handler->handle($request, RouteNotFoundException::by('Route not found'));
                $this->assertContains('<span class="status">404</span>Not Found', $response->getContent());
                $this->assertContains('Route not found', $response->getContent());
            }
        );

        $this->assertContainsStderr(
            [
                '[ERROR]',
                'HTTP 500 Internal Server Error occurred.',
                'Rebet\Config\Exception\ConfigNotDefineException: unit test in',
            ],
            function () use ($handler, $request) {
                $response = $handler->handle($request, ConfigNotDefineException::by('unit test'));
                $this->assertContains('<span class="status">500</span>Internal Server Error', $response->getContent());
                $this->assertContains('unit test', $response->getContent());
            }
        );
    }

    public function test_handle_json()
    {
        $request = $this->createJsonRequestMock('/');
        $handler = new BasicFallbackHandler();
        Config::application([
            StderrHandler::class => [
                'log_level' => LogLevel::TRACE(),
            ],
        ]);

        $this->assertContainsStderr(
            [
                '[TRACE]',
                'HTTP 403 Forbidden occurred.',
                'Rebet\Auth\Exception\AuthenticateException: Authentication failed in',
            ],
            function () use ($handler, $request) {
                $response = $handler->handle($request, AuthenticateException::by('Authentication failed'));
                $this->assertSame('application/json', $response->getHeader('Content-Type'));
                $this->assertSame('{"status":403,"title":"Forbidden","type":"about:blank","detail":"Authentication failed"}', $response->getContent());
            }
        );

        $this->assertContainsStderr(
            [
                '[TRACE]',
                'HTTP 404 Not Found occurred.',
                'Rebet\Routing\Exception\RouteNotFoundException: Route not found in',
            ],
            function () use ($handler, $request) {
                $response = $handler->handle($request, RouteNotFoundException::by('Route not found'));
                $this->assertSame('application/json', $response->getHeader('Content-Type'));
                $this->assertSame('{"status":404,"title":"Not Found","type":"about:blank","detail":"Route not found"}', $response->getContent());
            }
        );

        $this->assertContainsStderr(
            [
                '[ERROR]',
                'HTTP 500 Internal Server Error occurred.',
                'Rebet\Config\Exception\ConfigNotDefineException: unit test in',
            ],
            function () use ($handler, $request) {
                $response = $handler->handle($request, ConfigNotDefineException::by('unit test'));
                $this->assertSame('application/json', $response->getHeader('Content-Type'));
                $this->assertSame('{"status":500,"title":"Internal Server Error","type":"about:blank","detail":"unit test"}', $response->getContent());
            }
        );
    }
}

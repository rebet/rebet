<?php
namespace Rebet\Tests\Application\Routing;

use Rebet\Application\App;
use Rebet\Application\Routing\BasicFallbackHandler;
use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Config\Config;
use Rebet\Config\Exception\ConfigNotDefineException;
use Rebet\Log\Log;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class BasicFallbackHandlerTest extends RebetTestCase
{
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
    }

    public function test___construct()
    {
        $this->assertInstanceOf(BasicFallbackHandler::class, new BasicFallbackHandler());
    }

    public function test_handle_web()
    {
        App::setLocale('de', 'de');
        $request = $this->createRequestMock('/');
        $handler = new BasicFallbackHandler();

        $response = $handler->handle($request, AuthenticateException::by('Authentication failed'));
        $this->assertContains('<span class="status">403</span>Forbidden', $response->getContent());
        $this->assertContains('Authentication failed', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 403 Forbidden occurred.', $log);
        $this->assertContains('Rebet\Auth\Exception\AuthenticateException: Authentication failed in', $log);

        $response = $handler->handle($request, RouteNotFoundException::by('Route not found'));
        $this->assertContains('<span class="status">404</span>Not Found', $response->getContent());
        $this->assertContains('Route not found', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 404 Not Found occurred.', $log);
        $this->assertContains('Rebet\Routing\Exception\RouteNotFoundException: Route not found in', $log);

        $response = $handler->handle($request, ConfigNotDefineException::by('unit test'));
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
        $handler = new BasicFallbackHandler();

        $response = $handler->handle($request, AuthenticateException::by('Authentication failed'));
        $this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
        $this->assertSame('{"status":403,"title":"Forbidden","type":"about:blank","detail":"Authentication failed"}', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 403 Forbidden occurred.', $log);
        $this->assertContains('Rebet\Auth\Exception\AuthenticateException: Authentication failed in', $log);

        $response = $handler->handle($request, RouteNotFoundException::by('Route not found'));
        $this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
        $this->assertSame('{"status":404,"title":"Custom Not Found","type":"about:blank","detail":"The page could not be found. The specified URL is incorrect, or the page may have already been deleted \/ moved."}', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasDebugRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 403 Forbidden occurred.', $log);
        $this->assertContains('Rebet\Auth\Exception\AuthenticateException: Authentication failed in', $log);

        $response = $handler->handle($request, ConfigNotDefineException::by('unit test'));
        $this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
        $this->assertSame('{"status":500,"title":"Internal Server Error","type":"about:blank","detail":"unit test"}', $response->getContent());
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasErrorRecords());
        $log = $driver->formatted();
        $this->assertContains('HTTP 500 Internal Server Error occurred.', $log);
        $this->assertContains('Rebet\Config\Exception\ConfigNotDefineException: unit test in', $log);
    }
}

<?php
namespace Rebet\Tests\Application\Http;

use Rebet\Application\App;
use Rebet\Application\Http\WebExceptionHandler;
use Rebet\Application\Http\WebKernel;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Log\Log;
use Rebet\Routing\Router;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Template\Letterpress;

class WebKernelTest extends RebetTestCase
{
    /** @var WebKernel */
    protected $kernel;

    protected function setUp() : void
    {
        parent::setUp();
        Router::reset();
        Router::setCurrentChannel('web');
        Router::rules('web')->routing(function () {
            Router::get('/', function () {
                return 'Top: index';
            });
        });
        $this->kernel = new WebKernel(App::structure());
    }

    public function test___construct()
    {
        $kernel = new WebKernel(App::structure());
        $this->assertInstanceOf(WebKernel::class, $kernel);
        $this->assertSame('web', $kernel->channel());

        $kernel = new WebKernel(App::structure(), 'other');
        $this->assertSame('other', $kernel->channel());
    }

    public function test_structure()
    {
        $this->assertSame(App::structure(), $this->kernel->structure());
    }

    public function test_channel()
    {
        $this->assertSame('web', $this->kernel->channel());
    }

    public function test_bootstrap()
    {
        // The global test bootstrap (RebetTestCase::setUp()) already bootstrapped an AppWebKernel,
        // so re-registering process-wide singletons (like Letterpress tags) must be reset first.
        Letterpress::reset();
        $this->kernel->bootstrap();
        $this->assertInstanceOf(WebKernel::class, $this->kernel);

        // bootstrap() -> HandleExceptions pushes another error/exception handler onto the global
        // stack; pop the extra layer this test added so it doesn't leak into other tests.
        restore_error_handler();
        restore_exception_handler();
    }

    public function test_handle()
    {
        $request  = Request::create('/');
        $response = $this->kernel->handle($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Top: index', $response->getContent());
        $this->assertSame($request, $this->kernel->request());
    }

    public function test_call()
    {
        $response = $this->kernel->call('/');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Top: index', $response->getContent());
    }

    public function test_terminate()
    {
        $this->kernel->handle(Request::create('/'));
        $this->kernel->terminate();
        $this->assertTrue(true); // No exception raised.
    }

    public function test_fallback()
    {
        ob_start();
        $code    = $this->kernel->fallback(new \Exception('Kernel fallback test'));
        $content = ob_get_clean();
        $this->assertSame(0, $code);
        $this->assertStringContainsString('<span class="status">500</span>', $content);
        $this->assertStringContainsString('Kernel fallback test', $content);
    }

    public function test_report()
    {
        $this->kernel->report(new \Exception('Kernel report test'));
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasWarningRecords());
        $this->assertStringContainsString('Unhandled exception occurred.', $driver->formatted());
    }

    public function test_report_after_handle()
    {
        // Once a response is set, report() delegates to WebExceptionHandler's status-based leveling.
        $this->kernel->handle(Request::create('/'));
        $this->kernel->report(new \Exception('Kernel report after handle test'));
        $driver = Log::channel()->driver();
        $this->assertFalse($driver->hasWarningRecords());
        $this->assertFalse($driver->hasErrorRecords());
    }

    public function test_request()
    {
        $kernel = new WebKernel(App::structure());
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Request has not been set yet.');
        $kernel->request();
    }

    public function test_request_after_handle()
    {
        $request = Request::create('/');
        $this->kernel->handle($request);
        $this->assertSame($request, $this->kernel->request());
    }

    public function test_exceptionHandler()
    {
        $this->assertInstanceOf(WebExceptionHandler::class, $this->kernel->exceptionHandler());
        $this->assertNotSame($this->kernel->exceptionHandler(), $this->kernel->exceptionHandler());
    }
}

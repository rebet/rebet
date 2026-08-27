<?php
namespace Rebet\Tests\Application\Console;

use Rebet\Application\App;
use Rebet\Application\Console\Assistant;
use Rebet\Application\Console\CliExceptionHandler;
use Rebet\Application\Console\CliKernel;
use Rebet\Log\Log;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Template\Letterpress;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CliKernelTest extends RebetTestCase
{
    /** @var CliKernel */
    protected $kernel;

    /** @var BufferedOutput */
    protected $output;

    protected function setUp() : void
    {
        parent::setUp();
        // Avoid Symfony\Component\Console\Terminal shelling out to `stty` for terminal dimensions
        // (there's no real tty in the test environment).
        putenv('COLUMNS=80');
        putenv('LINES=24');
        // The global test bootstrap (RebetTestCase::setUp()) already bootstrapped an AppWebKernel,
        // so re-registering process-wide singletons (like Letterpress tags) must be reset first.
        Letterpress::reset();
        $this->output = new BufferedOutput();
        $this->kernel = new CliKernel(App::structure(), 'cli', $this->output);
        $this->kernel->bootstrap();
        // Symfony\Component\Console\Application::run() calls exit() by default after running a
        // command, which would kill the whole PHPUnit process; disable that for testing.
        $this->kernel->assistant()->setAutoExit(false);
    }

    protected function tearDown() : void
    {
        // bootstrap() -> HandleExceptions pushes another error/exception handler onto the global
        // stack on top of the one from the ambient AppWebKernel bootstrap; pop this test's layer
        // before the parent tearDown() pops that ambient one.
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function test___construct()
    {
        $kernel = new CliKernel(App::structure());
        $this->assertInstanceOf(CliKernel::class, $kernel);
        $this->assertSame('cli', $kernel->channel());

        $kernel = new CliKernel(App::structure(), 'other');
        $this->assertSame('other', $kernel->channel());
    }

    public function test_structure()
    {
        $this->assertSame(App::structure(), $this->kernel->structure());
    }

    public function test_channel()
    {
        $this->assertSame('cli', $this->kernel->channel());
    }

    public function test_bootstrap()
    {
        $kernel = new CliKernel(App::structure(), 'cli', new BufferedOutput());
        Letterpress::reset();
        $kernel->bootstrap();
        $this->assertInstanceOf(Assistant::class, $kernel->assistant());
        restore_error_handler();
        restore_exception_handler();
    }

    public function test_assistant()
    {
        $assistant = $this->kernel->assistant();
        $this->assertInstanceOf(Assistant::class, $assistant);
        $this->assertTrue($assistant->has('env'));
    }

    public function test_handle()
    {
        $status = $this->kernel->handle(new ArrayInput(['command' => 'env']));
        $this->assertSame(0, $status);
        $this->assertStringContainsString('Current application environment:', $this->output->fetch());
    }

    public function test_call()
    {
        $status = $this->kernel->call('env');
        $this->assertSame(0, $status);
        $this->assertStringContainsString('Current application environment:', $this->output->fetch());
    }

    public function test_terminate()
    {
        $this->kernel->handle(new ArrayInput(['command' => 'env']));
        $this->kernel->terminate();
        $this->assertTrue(true); // No exception raised.
    }

    public function test_fallback()
    {
        $code = $this->kernel->fallback(new \Exception('Kernel fallback test'));
        $this->assertSame(1, $code);
        $console = $this->output->fetch();
        $this->assertStringContainsString('Console Unhandled Exception Occurred', $console);
        $this->assertStringContainsString('Kernel fallback test', $console);
    }

    public function test_report()
    {
        $this->kernel->report(new \Exception('Kernel report test'));
        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasErrorRecords());
        $this->assertStringContainsString('Console unhandled exception occurred.', $driver->formatted());
    }

    public function test_exceptionHandler()
    {
        $handler = $this->kernel->exceptionHandler();
        $this->assertInstanceOf(CliExceptionHandler::class, $handler);

        // The handler must share the kernel's own output instance (not create its own ConsoleOutput).
        $handler->handle(null, new \Exception('exceptionHandler output test'));
        $this->assertStringContainsString('exceptionHandler output test', $this->output->fetch());
    }
}

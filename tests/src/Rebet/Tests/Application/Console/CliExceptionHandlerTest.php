<?php
namespace Rebet\Tests\Application\Console;

use Rebet\Application\Console\CliExceptionHandler;
use Rebet\Log\Log;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CliExceptionHandlerTest extends RebetTestCase
{
    /** @var CliExceptionHandler */
    public $handler;

    /** @var BufferedOutput */
    public $output;

    protected function setUp() : void
    {
        parent::setUp();
        $this->output  = new BufferedOutput();
        $this->handler = new class($this->output) extends CliExceptionHandler {
            public $reported_count = 0;

            public function report($input, $result, \Throwable $e) : void
            {
                $this->reported_count++;
            }
        };
    }

    public function test___construct()
    {
        $this->assertInstanceOf(CliExceptionHandler::class, new CliExceptionHandler());
        $this->assertInstanceOf(CliExceptionHandler::class, new CliExceptionHandler(new BufferedOutput()));
    }

    public function test_handle()
    {
        $reported_count = 0;
        $this->assertSame($reported_count, $this->handler->reported_count);

        $status = $this->handler->handle(null, new \Exception('Detail message'));
        $this->assertSame(++$reported_count, $this->handler->reported_count);
        $this->assertSame(1, $status);
        $console = $this->output->fetch();
        $this->assertStringContainsString('********************************************', $console);
        $this->assertStringContainsString('Console Unhandled Exception Occurred', $console);
        $this->assertStringContainsString('Detail message', $console);
        $this->assertStringContainsString('Exception:', $console);

        $status = $this->handler->handle(new ArrayInput([]), new \RuntimeException('Another error'));
        $this->assertSame(++$reported_count, $this->handler->reported_count);
        $this->assertSame(1, $status);
        $console = $this->output->fetch();
        $this->assertStringContainsString('Console Unhandled Exception Occurred', $console);
        $this->assertStringContainsString('Another error', $console);
    }

    public function test___invoke()
    {
        $status = $this->handler->__invoke(null, new \Exception('Detail message'));
        $this->assertSame(1, $this->handler->reported_count);
        $this->assertSame(1, $status);
        $console = $this->output->fetch();
        $this->assertStringContainsString('Console Unhandled Exception Occurred', $console);
        $this->assertStringContainsString('Detail message', $console);
    }

    public function test_handle_report()
    {
        $output  = new BufferedOutput();
        $handler = new CliExceptionHandler($output);
        $input   = new ArrayInput(['--verbose' => true]);

        $status = $handler->handle($input, new \Exception('Detail message'));
        $this->assertSame(1, $status);

        $console = $output->fetch();
        $this->assertStringContainsString('********************************************', $console);
        $this->assertStringContainsString('*   Console Unhandled Exception Occurred   *', $console);
        $this->assertStringContainsString('Detail message', $console);
        $this->assertStringContainsString('Exception:', $console);
        $this->assertStringContainsString(\Exception::class, $console);

        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasErrorRecords());
        $log = $driver->formatted();
        $this->assertStringContainsString('Console unhandled exception occurred. Error code: 1', $log);
        $this->assertStringContainsString('Exception: Detail message in', $log);
    }

    public function test_report()
    {
        $handler = new CliExceptionHandler(new BufferedOutput());
        $input   = new ArrayInput([]);

        $handler->report($input, 1, new \Exception('Report only message'));

        $driver = Log::channel()->driver();
        $this->assertTrue($driver->hasErrorRecords());
        $log = $driver->formatted();
        $this->assertStringContainsString('Console unhandled exception occurred. Error code: 1', $log);
        $this->assertStringContainsString('Exception: Report only message in', $log);
    }
}

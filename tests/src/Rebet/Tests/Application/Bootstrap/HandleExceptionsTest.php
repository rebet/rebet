<?php
namespace Rebet\Tests\Application\Bootstrap;

use Rebet\Application\Bootstrap\HandleExceptions;
use Rebet\Application\Kernel;
use Rebet\Tests\RebetTestCase;

class HandleExceptionsTest extends RebetTestCase
{
    public function test_bootstrap()
    {
        $fallbacked_exception = null;
        $reported_exception   = null;
        $is_terminated        = false;
        $kernel = $this->createMock(Kernel::class);
        $kernel->method('fallback')->willReturnCallback(function ($e) use (&$fallbacked_exception) { $fallbacked_exception = $e; return 1; });
        $kernel->method('report')->willReturnCallback(function ($e) use (&$reported_exception) { $reported_exception = $e; });
        $kernel->method('terminate')->willReturnCallback(function () use (&$is_terminated) { $is_terminated = true; });
        
        $bootstrapper = new HandleExceptions();
        $bootstrapper->bootstrap($kernel);

        // Test set_error_handler function.
        $error_handler = set_error_handler(null);
        try {
            $error_handler(E_USER_ERROR, 'Error message', '/path/to/error/file.php', 12);
        } catch(\ErrorException $e) {
            $this->assertInstanceOf(\ErrorException::class, $e);
            $this->assertSame($e->getCode(), 0);
            $this->assertSame($e->getSeverity(), E_USER_ERROR);
            $this->assertSame($e->getMessage(), 'Error message');
            $this->assertSame($e->getFile(), '/path/to/error/file.php');
            $this->assertSame($e->getLine(), 12);
        }

        // Test set_exception_handler function.
        $fallbaker = set_exception_handler(null);
        $exception = new \Exception("This is test");
        $this->assertNull($fallbacked_exception);
        $this->assertNull($reported_exception);
        $this->assertFalse($is_terminated);
        $fallbaker($exception);
        $this->assertSame($fallbacked_exception, $exception);
        $this->assertNull($reported_exception);
        $this->assertTrue($is_terminated);

        // Can not test register_shutdown_function function.

        // NOTE: RebetTestCase::setUp() already installs one pair of handlers via the app kernel's own
        //       HandleExceptions bootstrapper. This test installs a second pair via its explicit
        //       bootstrap() call, then pushes a third (null) pair via set_error_handler(null)/
        //       set_exception_handler(null) above to peek at the current handler. That is 3 pushes total,
        //       and RebetTestCase::tearDown() only restores once, so restore twice more here to balance
        //       the other two pushes.
        restore_error_handler();
        restore_error_handler();
        restore_exception_handler();
        restore_exception_handler();
    }
}

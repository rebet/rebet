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
        $kernel->method('fallback')->will($this->returnCallback(function ($e) use (&$fallbacked_exception) { $fallbacked_exception = $e; return 1; }));
        $kernel->method('report')->will($this->returnCallback(function ($e) use (&$reported_exception) { $reported_exception = $e; }));
        $kernel->method('terminate')->will($this->returnCallback(function () use (&$is_terminated) { $is_terminated = true; }));
        
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
    }
}

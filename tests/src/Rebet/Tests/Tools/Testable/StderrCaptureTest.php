<?php
namespace Rebet\Tests\Tools\Testable;

use function PHPUnit\Framework\assertEquals;
use Rebet\Tests\RebetTestCase;

use Rebet\Tools\Testable\StderrCapture;

class StderrCaptureTest extends RebetTestCase
{
    public function test_startAndAppendAndStop()
    {
        StderrCapture::start();
        fputs(STDERR, 'foo');
        $stderr = StderrCapture::append(fopen('php://stderr', 'w'));
        fwrite($stderr, 'bar');
        fclose($stderr);
        $captured = StderrCapture::stop();
        assertEquals('foobar', $captured);
    }

    public function test_via()
    {
        $captured = StderrCapture::via(function () {
            fputs(STDERR, 'foo');
            $stderr = StderrCapture::append(fopen('php://stderr', 'w'));
            fwrite($stderr, 'bar');
            fclose($stderr);
        });
        assertEquals('foobar', $captured);
    }
}

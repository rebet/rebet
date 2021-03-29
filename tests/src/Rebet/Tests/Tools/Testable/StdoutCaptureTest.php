<?php
namespace Rebet\Tests\Tools\Testable;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Testable\StdoutCapture;

use function PHPUnit\Framework\assertEquals;

class StdoutCaptureTest extends RebetTestCase
{
    public function test_startAndStop()
    {
        StdoutCapture::start();
        echo 'foo';
        fputs(STDOUT, 'bar');
        $stdout = fopen('php://stdout', 'w');
        StdoutCapture::append($stdout);
        fwrite($stdout, 'baz');
        fclose($stdout);
        echo 'qux';
        $captured = StdoutCapture::stop();
        assertEquals('foobarbazqux', $captured);
    }

    public function test_via()
    {
        $captured = StdoutCapture::via(function(){
            echo 'foo';
            fputs(STDOUT, 'bar');
            $stdout = fopen('php://stdout', 'w');
            StdoutCapture::append($stdout);
            fwrite($stdout, 'baz');
            fclose($stdout);
            echo 'qux';
        });
        assertEquals('foobarbazqux', $captured);
    }
}

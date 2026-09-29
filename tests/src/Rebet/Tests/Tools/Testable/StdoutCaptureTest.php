<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\Testable;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Testable\StdoutCapture;

use function PHPUnit\Framework\assertEquals;

class StdoutCaptureTest extends RebetTestCase
{
    public function test_startAndStop(): void
    {
        StdoutCapture::start();
        echo 'foo';
        fwrite(STDOUT, 'bar');
        $stdout = fopen('php://stdout', 'w');
        StdoutCapture::append($stdout);
        fwrite($stdout, 'baz');
        fclose($stdout);
        echo 'qux';
        $captured = StdoutCapture::stop();
        assertEquals('foobarbazqux', $captured);
    }

    public function test_via(): void
    {
        $captured = StdoutCapture::via(function (): void {
            echo 'foo';
            fwrite(STDOUT, 'bar');
            $stdout = fopen('php://stdout', 'w');
            StdoutCapture::append($stdout);
            fwrite($stdout, 'baz');
            fclose($stdout);
            echo 'qux';
        });
        assertEquals('foobarbazqux', $captured);
    }
}

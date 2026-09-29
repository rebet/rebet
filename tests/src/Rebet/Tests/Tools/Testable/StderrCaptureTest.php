<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\Testable;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Testable\StderrCapture;

use function PHPUnit\Framework\assertEquals;

class StderrCaptureTest extends RebetTestCase
{
    public function test_startAndAppendAndStop(): void
    {
        StderrCapture::start();
        fputs(STDERR, 'foo');
        $stderr = StderrCapture::append(fopen('php://stderr', 'w'));
        fwrite($stderr, 'bar');
        fclose($stderr);
        $captured = StderrCapture::stop();
        assertEquals('foobar', $captured);
    }

    public function test_via(): void
    {
        $captured = StderrCapture::via(function (): void {
            fputs(STDERR, 'foo');
            $stderr = StderrCapture::append(fopen('php://stderr', 'w'));
            fwrite($stderr, 'bar');
            fclose($stderr);
        });
        assertEquals('foobar', $captured);
    }
}

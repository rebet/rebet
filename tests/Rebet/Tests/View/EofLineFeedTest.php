<?php
namespace Rebet\Tests\View;

use Rebet\Tests\RebetTestCase;
use Rebet\View\EofLineFeed;

class EofLineFeedTest extends RebetTestCase
{
    public function test_process()
    {
        $contents = "a\r\nb\r\n\r\n";
        $this->assertSame(null, EofLineFeed::TRIM()->process(null));
        $this->assertSame("a\r\nb", EofLineFeed::TRIM()->process($contents));
        $this->assertSame("a\r\nb\r\n\r\n", EofLineFeed::KEEP()->process($contents));
        $this->assertSame("a\r\nb\n", EofLineFeed::ONE()->process($contents));
    }
}

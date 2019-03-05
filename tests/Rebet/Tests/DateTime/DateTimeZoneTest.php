<?php
namespace Rebet\Tests\DateTime;

use Rebet\DateTime\DateTimeZone;
use Rebet\Tests\RebetTestCase;

class DateTimeZoneTest extends RebetTestCase
{
    public function test_construct()
    {
        $rebet  = new DateTimeZone("UTC");
        $this->assertSame("UTC", $rebet->getName());

        $origin = new \DateTimeZone("UTC");
        $rebet  = new DateTimeZone($origin);
        $this->assertSame("UTC", $rebet->getName());

        $rebet2 = new DateTimeZone($rebet);
        $this->assertSame("UTC", $rebet2->getName());
    }

    public function test_toString()
    {
        $rebet = new DateTimeZone("UTC");
        $this->assertSame("UTC", "$rebet");
    }

    public function test_convertTo()
    {
        $rebet = new DateTimeZone("UTC");
        $this->assertSame($rebet, $rebet->convertTo(DateTimeZone::class));
        $this->assertSame($rebet, $rebet->convertTo(\DateTimeZone::class));
        $this->assertSame('UTC', $rebet->convertTo('string'));
        $this->assertSame(null, $rebet->convertTo('int'));
    }
}

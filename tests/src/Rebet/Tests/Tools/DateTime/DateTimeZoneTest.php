<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\DateTime;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTimeZone;

class DateTimeZoneTest extends RebetTestCase
{
    public function test_construct(): void
    {
        $rebet = new DateTimeZone("UTC");
        $this->assertSame("UTC", $rebet->getName());

        $origin = new \DateTimeZone("UTC");
        $rebet  = new DateTimeZone($origin);
        $this->assertSame("UTC", $rebet->getName());

        $rebet2 = new DateTimeZone($rebet);
        $this->assertSame("UTC", $rebet2->getName());
    }

    public function test_valueOf(): void
    {
        $rebet = DateTimeZone::valueOf("UTC");
        $this->assertSame("UTC", $rebet->getName());
    }

    public function test_toString(): void
    {
        $rebet = new DateTimeZone("UTC");
        $this->assertSame("UTC", "$rebet");
    }

    public function test_convertTo(): void
    {
        $rebet = new DateTimeZone("UTC");
        $this->assertSame($rebet, $rebet->convertTo(DateTimeZone::class));
        $this->assertSame($rebet, $rebet->convertTo(\DateTimeZone::class));
        $this->assertSame('UTC', $rebet->convertTo('string'));
        $this->assertSame(null, $rebet->convertTo('int'));
    }
}

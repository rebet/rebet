<?php
namespace Rebet\Tests\DateTime;

use Rebet\Tests\RebetTestCase;
use Rebet\DateTime\DateTimeZone;
use Rebet\Config\App;

class DateTimeZoneTest extends RebetTestCase {

    public function test_construct() {
        $rebet  = new DateTimeZone("UTC");
        $this->assertSame("UTC", $rebet->getName());

        $origin = new \DateTimeZone("UTC");
        $rebet  = new DateTimeZone($origin);
        $this->assertSame("UTC", $rebet->getName());
        
        $rebet2 = new DateTimeZone($rebet);
        $this->assertSame("UTC", $rebet2->getName());
    }

    public function test_toString() {
        $rebet = new DateTimeZone("UTC");
        $this->assertSame("UTC", "$rebet");
    }
}

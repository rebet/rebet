<?php
namespace Rebet\Tests\DateTime;

use Rebet\Common\Strings;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\DateTime\DateTimeZone;
use Rebet\Tests\RebetTestCase;
use Rebet\DateTime\Date;

class DateTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30');
    }

    public function test___construct()
    {
        $date = new Date();
        $this->assertInstanceOf(Date::class, $date);
        $this->assertSame('2010-10-20', $date->__toString());
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $date = new Date('now');
        $this->assertInstanceOf(Date::class, $date);
        $this->assertSame('2010-10-20', $date->__toString());
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $date = Date::today();
        $this->assertInstanceOf(Date::class, $date);
        $this->assertSame('2010-10-20', $date->__toString());
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $date = Date::now();
        $this->assertInstanceOf(Date::class, $date);
        $this->assertSame('2010-10-20', $date->__toString());
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
    }
}

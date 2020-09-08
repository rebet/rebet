<?php
namespace Rebet\Tests\DateTime;

use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\RebetTestCase;

class DateTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
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

    public function test_timeModification()
    {
        $date = Date::now();
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->addMinute(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addHour(12)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->addHour(1 + 24)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->setHour(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setHour(12)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setHour(1 + 24)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->addMinute(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMinute(30)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMinute(12 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->addMinute(1 + 24 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->setMinute(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMinute(30)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMinute(12 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setMinute(1 + 24 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->addSecond(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addSecond(30)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addSecond(12 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addSecond(12 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->addSecond(1 + 24 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->setSecond(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setSecond(30)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setSecond(12 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setSecond(12 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setSecond(1 + 24 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->addMilli(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilli(500)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilli(12 * 1000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilli(12 * 1000 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilli(12 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->addMilli(1 + 24 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->setMilli(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilli(500)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilli(12 * 1000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilli(12 * 1000 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilli(12 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setMilli(1 + 24 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->addMicro(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMicro(500)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMicro(12 * 1000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMicro(12 * 1000 * 1000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMicro(12 * 1000 * 1000 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMicro(12 * 1000 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->addMicro(1 + 24 * 1000 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->setMicro(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMicro(500)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMicro(12 * 1000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMicro(12 * 1000 * 1000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMicro(12 * 1000 * 1000 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMicro(12 * 1000 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setMicro(1 + 24 * 1000 * 1000 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->addMilliMicro(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilliMicro(500)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilliMicro(500000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilliMicro(12 * 1000000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilliMicro(12 * 1000000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilliMicro(12 * 1000000 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->addMilliMicro(12 * 1000000 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->addMilliMicro(1 + 24 * 1000000 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->setMilliMicro(-1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilliMicro(500)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilliMicro(500000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilliMicro(12 * 1000000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilliMicro(12 * 1000000)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilliMicro(12 * 1000000 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setMilliMicro(12 * 1000000 * 60 * 60)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setMilliMicro(1 + 24 * 1000000 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->modify('-1 hour')->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->modify('+12 hour')->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->modify('+25 hour')->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $date->setTime(0, 0, 0, -1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-19 00:00:00.000000', $date->setTime(0, 0, -1)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setTime(1, 2, 3)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setTime(25, 0, 0)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setTime(0, 25 * 60, 0)->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $date->setTime(0, 0, 25 * 60 * 60)->format('Y-m-d H:i:s.u'));

        $now = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.123456', $now->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->setTimestamp($now->getTimestamp())->format('Y-m-d H:i:s.u'));

        $date_utc   = Date::now('UTC');
        $date_tokyo = $date_utc->setTimezone('Asia/Tokyo');
        $this->assertSame('2010-10-20 00:00:00.000000', $date_utc->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date_tokyo->format('Y-m-d H:i:s.u'));

        $now = Date::now();
        $this->assertSame('2010-10-20 00:00:00.000000', $now->add('PT01H')->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-20 00:00:00.000000', $now->add('PT12H')->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-21 00:00:00.000000', $now->add('PT25H')->format('Y-m-d H:i:s.u'));

        $this->assertSame('2010-10-19 00:00:00.000000', $now->sub('PT01H')->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-19 00:00:00.000000', $now->sub('PT12H')->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-10-18 00:00:00.000000', $now->sub('PT25H')->format('Y-m-d H:i:s.u'));
    }

    public function test_toDate()
    {
        $date = new Date();
        $this->assertSame($date, $date->toDate());
    }

    public function test_toDateTime()
    {
        $date     = new Date();
        $datetime = $date->toDateTime();
        $this->assertInstanceOf(DateTime::class, $datetime);
    }
}

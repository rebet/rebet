<?php
namespace Rebet\Tests\DateTime;

use Rebet\Tests\RebetTestCase;
use Rebet\DateTime\DateTime;
use Rebet\Config\Config;
use Rebet\Config\App;

class DateTimeTest extends RebetTestCase {
    public function setUp() {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2000-01-01 00:00:00');
    }

    public function test_setTestNow() {
        DateTime::setTestNow('2000-01-01 00:00:00');
        $this->assertSame('2000-01-01 00:00:00', DateTime::getTestNow());

        DateTime::setTestNow('2000-01-01 00:00:00.12345');
        $this->assertSame('2000-01-01 00:00:00.12345', DateTime::getTestNow());
    }

    public function test_getTestNow() {
        DateTime::setTestNow('2000-01-01 00:00:00');
        $this->assertSame('2000-01-01 00:00:00', DateTime::getTestNow());

        DateTime::setTestNow('2000-01-01 00:00:00.12345');
        $this->assertSame('2000-01-01 00:00:00.12345', DateTime::getTestNow());
    }

    public function test_removeTestNow() {
        DateTime::setTestNow('2000-01-01 00:00:00');
        $this->assertSame('2000-01-01 00:00:00', DateTime::getTestNow());
        DateTime::removeTestNow();
        $this->assertNull(DateTime::getTestNow());
    }

    public function test_construct() {
        DateTime::setTestNow('2000-01-01 00:00:00');

        $date = new DateTime();
        $this->assertSame('2000-01-01 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('2000-01-01 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('yesterday');
        $this->assertSame('1999-12-31 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('noon');
        $this->assertSame('2000-01-01 12:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('tomorrow');
        $this->assertSame('2000-01-02 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('tomorrow noon');
        $this->assertSame('2000-01-02 12:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('+1 day');
        $this->assertSame('2000-01-02 00:00:00', $date->format('Y-m-d H:i:s'));


        DateTime::setTestNow('2000-01-01 00:00:00.123456');

        $date = new DateTime();
        $this->assertSame('2000-01-01 00:00:00.123456', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('+1 day +2 hour');
        $this->assertSame('2000-01-02 02:00:00.123456', $date->format('Y-m-d H:i:s.u'));


        DateTime::setTestNow('2000-01-01 00:00');

        $date = new DateTime();
        $this->assertSame('2000-01-01 00:00', $date->format('Y-m-d H:i'));
        $this->assertSame('2000-01-01 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));


        DateTime::setTestNow('2000-01-01');

        $date = new DateTime();
        $this->assertSame('2000-01-01', $date->format('Y-m-d'));
        $this->assertSame('2000-01-01 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('now', 'Asia/Tokyo');
        $this->assertSame('2000-01-01', $date->format('Y-m-d'));
    }

    public function test_toString() {
        $date = new DateTime();
        $this->assertSame('2000-01-01 00:00:00', "{$date}");
    }
}

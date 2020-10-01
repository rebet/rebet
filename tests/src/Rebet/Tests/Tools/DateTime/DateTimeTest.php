<?php
namespace Rebet\Tests\Tools\DateTime;

use Rebet\Application\App;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Resource\Resource;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\DateTime\DateTimeZone;
use Rebet\Tools\DateTime\DayOfWeek;
use Rebet\Tools\DateTime\Month;
use Rebet\Tools\Path;
use Rebet\Tools\Strings;
use Rebet\Tools\Translation\Translator;

class DateTimeTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30');
    }

    public function test_setTestNow()
    {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('2010-10-20 10:20:30', DateTime::getTestNow());
        $this->assertSame('UTC', DateTime::getTestNowTimezone());

        DateTime::setTestNow('2010-10-20 10:20:30.12345', 'Asia/Tokyo');
        $this->assertSame('2010-10-20 10:20:30.12345', DateTime::getTestNow());
        $this->assertSame('Asia/Tokyo', DateTime::getTestNowTimezone());
    }

    public function test_getTestNow()
    {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('2010-10-20 10:20:30', DateTime::getTestNow());

        DateTime::setTestNow('2010-10-20 10:20:30.12345');
        $this->assertSame('2010-10-20 10:20:30.12345', DateTime::getTestNow());
    }

    public function test_setTestNowTimezone()
    {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('UTC', DateTime::getTestNowTimezone());

        DateTime::setTestNow('2010-10-20 10:20:30.12345', 'Asia/Tokyo');
        $this->assertSame('Asia/Tokyo', DateTime::getTestNowTimezone());
    }

    public function test_removeTestNow()
    {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('2010-10-20 10:20:30', DateTime::getTestNow());
        DateTime::removeTestNow();
        $this->assertNull(DateTime::getTestNow());
        $this->assertNull(DateTime::getTestNowTimezone());
    }

    public function test_freeze()
    {
        DateTime::removeTestNow();

        $now = DateTime::now();
        usleep(1100);
        $this->assertNotEquals($now, DateTime::now());
        usleep(1100);
        $this->assertNotEquals($now, new DateTime('now'));

        $result = DateTime::freeze(function () {
            $now = DateTime::now();
            usleep(1100);
            $this->assertEquals($now, DateTime::now());
            usleep(1100);
            $this->assertEquals($now, new DateTime('now'));

            return 'foo';
        });
        $this->assertSame('foo', $result);

        $now    = DateTime::now();
        $result = DateTime::freeze(function () use ($now) {
            usleep(1100);
            $this->assertEquals($now, DateTime::now());
            usleep(1100);
            $this->assertEquals($now, new DateTime());

            return new DateTime('now');
        }, $now);
        $this->assertEquals($now, $result);
    }

    public function test_construct()
    {
        $date = new DateTime();
        $this->assertSame('2010-10-20 10:20:30', $date->format('Y-m-d H:i:s'));
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('yesterday');
        $this->assertSame('2010-10-19 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('today');
        $this->assertSame('2010-10-20 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('noon');
        $this->assertSame('2010-10-20 12:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('tomorrow');
        $this->assertSame('2010-10-21 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('tomorrow noon');
        $this->assertSame('2010-10-21 12:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('+1 day');
        $this->assertSame('2010-10-21 10:20:30', $date->format('Y-m-d H:i:s'));


        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = new DateTime();
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('+1 day +2 hour');
        $this->assertSame('2010-10-21 12:20:30.123456', $date->format('Y-m-d H:i:s.u'));


        DateTime::setTestNow('2010-10-20 00:00');

        $date = new DateTime();
        $this->assertSame('2010-10-20 00:00', $date->format('Y-m-d H:i'));
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));


        DateTime::setTestNow('2010-10-20 10:20:30');

        $date = new DateTime();
        $this->assertSame('2010-10-20 10:20:30', $date->format('Y-m-d H:i:s'));
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('now', 'Asia/Tokyo');
        $this->assertSame('2010-10-20 19:20:30', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('now', new \DateTimeZone('Asia/Tokyo'));
        $this->assertSame('2010-10-20 19:20:30', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
        $this->assertSame('2010-10-20 19:20:30', $date->format('Y-m-d H:i:s'));

        $org_date = \DateTime::createFromFormat('Y-m-d H:i:s', '2010-10-20 10:20:30', new \DateTimeZone('Asia/Tokyo'));
        $date     = new DateTime($org_date);
        $this->assertSame('2010-10-20 10:20:30', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());

        $date = new DateTime($org_date, 'UTC');
        $this->assertSame('2010-10-20 01:20:30', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());

        $date = new DateTime('2000-01-23 04:56');
        $this->assertEquals($date, new DateTime($date->getTimestamp()));

        for ($i = 0; $i < 100; $i++) {
            $microtime = microtime(true);
            $date      = new DateTime($microtime);
            $this->assertStringStartsWith((string)$microtime, $date->format('U.u'));
            $this->assertEquals(floatval((string)$microtime), $date->getMicroTimestamp());
        }
    }

    public function test_valueOf()
    {
        $date = DateTime::valueOf('2010-10-20 10:20:30.123456');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));

        $date = DateTime::valueOf('now');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));

        $date = DateTime::valueOf('invalid');
        $this->assertNull($date);

        $date = DateTime::valueOf(null);
        $this->assertNull($date);
    }

    public function test_createFromFormat()
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-20 10:20:30.123456');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));

        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', null);
        $this->assertFalse($date);

        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '');
        $this->assertFalse($date);

        $input = new DateTime();
        $date  = DateTime::createFromFormat('Y-m-d H:i:s.u', $input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertEquals($input, $date);

        $input = new \DateTime('2010-10-20 10:20:30.123456');
        $date  = DateTime::createFromFormat('Y-m-d H:i:s.u', $input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));

        $input = new \DateTimeImmutable('2010-10-20 10:20:30.123456');
        $date  = DateTime::createFromFormat('Y-m-d H:i:s.u', $input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));

        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010/10/10');
        $this->assertFalse($date);

        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-20 10:20:30.123456', 'Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));

        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-20 10:20:30.123456', new DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));

        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-20 10:20:30.123456', new \DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
    }

    public function test_analyzeDateTime()
    {
        DateTime::setTestNow('2010-10-20 01:02:03.456789');

        $input                 = '2010-10-20 12:34:56';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y-m-d H:i:s', $apply_format);
        $this->assertSame('2010-10-20 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));

        $input                 = '2010/10/20 12:34';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y/m/d H:i', $apply_format);
        $this->assertSame('2010-10-20 12:34:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));

        $input                 = '20101020';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Ymd', $apply_format);
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));

        [$date, $apply_format] = DateTime::analyzeDateTime(null);
        $this->assertNull($date);
        $this->assertNull($apply_format);

        [$date, $apply_format] = DateTime::analyzeDateTime('');
        $this->assertNull($date);
        $this->assertNull($apply_format);

        $input                 = new DateTime();
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertEquals($input, $date);
        $this->assertSame(DateTime::config('default_format'), $apply_format);

        $input                 = new \DateTime('2010-10-20 01:02:03.456789');
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        $this->assertSame(DateTime::config('default_format'), $apply_format);

        $input                 = new \DateTimeImmutable('2010-10-20 01:02:03.456789');
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        $this->assertSame(DateTime::config('default_format'), $apply_format);

        $input                 = '2010.10.20';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertNull($date);
        $this->assertNull($apply_format);

        [$date, $apply_format] = DateTime::analyzeDateTime($input, ['Y.m.d']);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y.m.d', $apply_format);
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));

        $input                 = '10/01, 2010';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertNull($date);
        $this->assertNull($apply_format);

        [$date, $apply_format] = DateTime::analyzeDateTime($input, ['Y.m.d', 'm/d, Y']);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('m/d, Y', $apply_format);
        $this->assertSame('2010-10-01 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));

        $input                 = '2010-10-20 12:34:56';
        [$date, $apply_format] = DateTime::analyzeDateTime($input, [], 'Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('Y-m-d H:i:s', $apply_format);
        $this->assertSame('2010-10-20 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));

        $input = new DateTime();
        $this->assertSame('UTC', $input->getTimezone()->getName());
        $this->assertSame('2010-10-20 01:02:03.456789', $input->format('Y-m-d H:i:s.u'));
        [$date, $apply_format] = DateTime::analyzeDateTime($input, [], 'Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:02:03.456789', $date->format('Y-m-d H:i:s.u'));

        Config::runtime([
            DateTime::class => [
                'default_format' => 'Y m d'
            ]
        ]);

        $input                 = '2010 01 02';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y m d', $apply_format);
        $this->assertSame('2010-01-02 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
    }

    public function test_createDateTime()
    {
        DateTime::setTestNow('2010-10-20 01:02:03.456789');

        $input = '2010-10-20 12:34:56';
        $date  = DateTime::createDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
    }

    public function test_add()
    {
        $date = new DateTime();
        $new  = $date->add(new \DateInterval('P1D'));
        $this->assertInstanceOf(DateTime::class, $new);

        $new  = $date->add('P1D');
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-21 10:20:30', $new->format('Y-m-d H:i:s'));
    }

    public function test_modify()
    {
        $date = new DateTime();
        $new  = $date->modify('+1 day');
        $this->assertInstanceOf(DateTime::class, $new);
    }

    public function test_setDate()
    {
        $date = new DateTime();
        $new  = $date->setDate(2011, 11, 12);
        $this->assertInstanceOf(DateTime::class, $new);
    }

    public function test_setISODate()
    {
        $date = new DateTime();
        $new  = $date->setISODate(2010, 1);
        $this->assertInstanceOf(DateTime::class, $new);
    }

    public function test_setTime()
    {
        $date = new DateTime();
        $new  = $date->setTime(10, 11);
        $this->assertInstanceOf(DateTime::class, $new);
    }

    public function test_setTimestamp()
    {
        $date = new DateTime();
        $new  = $date->setTimestamp(time());
        $this->assertInstanceOf(DateTime::class, $new);
    }

    public function test_setTimezone()
    {
        $date = new DateTime();
        $new  = $date->setTimezone('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('Asia/Tokyo', $new->getTimezone()->getName());

        $date = new DateTime();
        $new  = $date->setTimezone(new DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('Asia/Tokyo', $new->getTimezone()->getName());

        $date = new DateTime();
        $new  = $date->setTimezone(new \DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('Asia/Tokyo', $new->getTimezone()->getName());
    }

    public function test___toString()
    {
        $now = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30', "{$now}");

        $now->setDefaultFormat('Y/m/d H:i:s');
        $this->assertSame('2010/10/20 10:20:30', "{$now}");
    }

    public function test_sub()
    {
        $date = new DateTime();
        $new  = $date->sub(new \DateInterval('P1D'));
        $this->assertInstanceOf(DateTime::class, $new);

        $new  = $date->sub('P1D');
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-19 10:20:30', $new->format('Y-m-d H:i:s'));
    }

    public function test_setDefaultFormat()
    {
        $date = new DateTime();
        $this->assertSame('2010-10-20 10:20:30', "{$date}");

        $date->setDefaultFormat('Y/m/d');
        $this->assertSame('2010/10/20', "{$date}");
    }

    public function test_toString()
    {
        $date = new DateTime();
        $this->assertSame('2010-10-20 10:20:30', "{$date}");

        $date->setDefaultFormat('Y/m/d');
        $this->assertSame('2010/10/20', "{$date}");
    }

    public function test_jsonSerialize()
    {
        $date = new DateTime();
        $this->assertSame('2010-10-20 10:20:30', $date->jsonSerialize());

        $date->setDefaultFormat('Y/m/d');
        $this->assertSame('2010/10/20', $date->jsonSerialize());
    }

    public function test_now()
    {
        $now = DateTime::now();
        $this->assertInstanceOf(DateTime::class, $now);
        $this->assertSame('UTC', $now->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.000000', $now->format('Y-m-d H:i:s.u'));

        $now = DateTime::now('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $now);
        $this->assertSame('Asia/Tokyo', $now->getTimezone()->getName());
        $this->assertSame('2010-10-20 19:20:30.000000', $now->format('Y-m-d H:i:s.u'));
    }

    public function test_today()
    {
        $today = DateTime::today();
        $this->assertInstanceOf(DateTime::class, $today);
        $this->assertSame('UTC', $today->getTimezone()->getName());
        $this->assertSame('2010-10-20 00:00:00.000000', $today->format('Y-m-d H:i:s.u'));

        $today = DateTime::today('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $today);
        $this->assertSame('Asia/Tokyo', $today->getTimezone()->getName());
        $this->assertSame('2010-10-20 00:00:00.000000', $today->format('Y-m-d H:i:s.u'));
    }

    public function test_yesterday()
    {
        $yesterday = DateTime::yesterday();
        $this->assertInstanceOf(DateTime::class, $yesterday);
        $this->assertSame('UTC', $yesterday->getTimezone()->getName());
        $this->assertSame('2010-10-19 00:00:00.000000', $yesterday->format('Y-m-d H:i:s.u'));

        $yesterday = DateTime::yesterday('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $yesterday);
        $this->assertSame('Asia/Tokyo', $yesterday->getTimezone()->getName());
        $this->assertSame('2010-10-19 00:00:00.000000', $yesterday->format('Y-m-d H:i:s.u'));
    }

    public function test_tomorrow()
    {
        $tomorrow = DateTime::tomorrow();
        $this->assertInstanceOf(DateTime::class, $tomorrow);
        $this->assertSame('UTC', $tomorrow->getTimezone()->getName());
        $this->assertSame('2010-10-21 00:00:00.000000', $tomorrow->format('Y-m-d H:i:s.u'));

        $tomorrow = DateTime::tomorrow('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $tomorrow);
        $this->assertSame('Asia/Tokyo', $tomorrow->getTimezone()->getName());
        $this->assertSame('2010-10-21 00:00:00.000000', $tomorrow->format('Y-m-d H:i:s.u'));
    }

    public function test_addYear()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addYear(1);
        $this->assertSame('2011-10-20 10:20:30.000000', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));

        $last = $date->addYear(-1);
        $this->assertSame('2009-10-20 10:20:30.000000', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setYear()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setYear(2011);
        $this->assertSame('2011-10-20 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
    }

    public function test_getYear()
    {
        $date = DateTime::now();
        $year = $date->getYear();
        $this->assertIsInt($year);
        $this->assertSame(2010, $year);
    }

    public function test_addMonth()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMonth(1);
        $this->assertSame('2010-11-20 10:20:30.000000', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMonth(3);
        $this->assertSame('2011-01-20 10:20:30.000000', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addMonth(-1);
        $this->assertSame('2010-09-20 10:20:30.000000', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMonth(-10);
        $this->assertSame('2009-12-20 10:20:30.000000', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setMonth()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setMonth(11);
        $this->assertSame('2010-11-20 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setMonth(13);
        $this->assertSame('2011-01-20 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));

        $new = $date->setMonth(-1);
        $this->assertSame('2009-11-20 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));
    }

    public function test_getMonth()
    {
        $date  = DateTime::now();
        $month = $date->getMonth();
        $this->assertIsInt($month);
        $this->assertSame(10, $month);
    }

    public function test_getLocalizedMonth()
    {
        $date  = DateTime::now();
        $month = $date->getLocalizedMonth();
        $this->assertInstanceOf(Month::class, $month);
        $this->assertEquals(Month::OCTOBER(), $month);
        $this->assertSame('10月', "{$month}");

        Translator::setLocale('en');
        $this->assertSame('October', "{$month}");
    }

    public function test_addDay()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addDay(1);
        $this->assertSame('2010-10-21 10:20:30.000000', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addDay(12);
        $this->assertSame('2010-11-01 10:20:30.000000', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addDay(-1);
        $this->assertSame('2010-10-19 10:20:30.000000', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addDay(-20);
        $this->assertSame('2010-09-30 10:20:30.000000', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setDay()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setDay(21);
        $this->assertSame('2010-10-21 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setDay(32);
        $this->assertSame('2010-11-01 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));

        $new = $date->setDay(-1);
        $this->assertSame('2010-09-29 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));
    }

    public function test_getDay()
    {
        $date = DateTime::now();
        $day  = $date->getDay();
        $this->assertIsInt($day);
        $this->assertSame(20, $day);
    }

    public function test_addHour()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addHour(1);
        $this->assertSame('2010-10-20 11:20:30.000000', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addHour(15);
        $this->assertSame('2010-10-21 01:20:30.000000', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addHour(-1);
        $this->assertSame('2010-10-20 09:20:30.000000', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addHour(-11);
        $this->assertSame('2010-10-19 23:20:30.000000', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setHour()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setHour(11);
        $this->assertSame('2010-10-20 11:20:30.000000', $new->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setHour(25);
        $this->assertSame('2010-10-21 01:20:30.000000', $new->format('Y-m-d H:i:s.u'));

        $new = $date->setHour(-1);
        $this->assertSame('2010-10-19 23:20:30.000000', $new->format('Y-m-d H:i:s.u'));
    }

    public function test_getHour()
    {
        $date = DateTime::now();
        $hour = $date->getHour();
        $this->assertIsInt($hour);
        $this->assertSame(10, $hour);
    }

    public function test_addMinute()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMinute(1);
        $this->assertSame('2010-10-20 10:21:30.000000', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMinute(41);
        $this->assertSame('2010-10-20 11:01:30.000000', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addMinute(-1);
        $this->assertSame('2010-10-20 10:19:30.000000', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMinute(-21);
        $this->assertSame('2010-10-20 09:59:30.000000', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setMinute()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setMinute(21);
        $this->assertSame('2010-10-20 10:21:30.000000', $new->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setMinute(61);
        $this->assertSame('2010-10-20 11:01:30.000000', $new->format('Y-m-d H:i:s.u'));

        $new = $date->setMinute(-1);
        $this->assertSame('2010-10-20 09:59:30.000000', $new->format('Y-m-d H:i:s.u'));
    }

    public function test_getMinute()
    {
        $date   = DateTime::now();
        $minute = $date->getMinute();
        $this->assertIsInt($minute);
        $this->assertSame(20, $minute);
    }

    public function test_addSecond()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addSecond(1);
        $this->assertSame('2010-10-20 10:20:31.000000', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addSecond(31);
        $this->assertSame('2010-10-20 10:21:01.000000', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addSecond(-1);
        $this->assertSame('2010-10-20 10:20:29.000000', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addSecond(-31);
        $this->assertSame('2010-10-20 10:19:59.000000', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setSecond()
    {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setSecond(31);
        $this->assertSame('2010-10-20 10:20:31.000000', $new->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setSecond(61);
        $this->assertSame('2010-10-20 10:21:01.000000', $new->format('Y-m-d H:i:s.u'));

        $new = $date->setSecond(-1);
        $this->assertSame('2010-10-20 10:19:59.000000', $new->format('Y-m-d H:i:s.u'));
    }

    public function test_getSecond()
    {
        $date   = DateTime::now();
        $second = $date->getSecond();
        $this->assertIsInt($second);
        $this->assertSame(30, $second);
    }

    public function test_addMilliMicro()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMilliMicro(1);
        $this->assertSame('2010-10-20 10:20:30.123457', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMilliMicro(876543);
        $this->assertSame('2010-10-20 10:20:30.999999', $next->format('Y-m-d H:i:s.u'));
        $next = $date->addMilliMicro(876545);
        $this->assertSame('2010-10-20 10:20:31.000001', $next->format('Y-m-d H:i:s.u'));
        $next = $date->addMilliMicro(1876545);
        $this->assertSame('2010-10-20 10:20:32.000001', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addMilliMicro(-1);
        $this->assertSame('2010-10-20 10:20:30.123455', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMilliMicro(-123456);
        $this->assertSame('2010-10-20 10:20:30.000000', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMilliMicro(-123457);
        $this->assertSame('2010-10-20 10:20:29.999999', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMilliMicro(-1123458);
        $this->assertSame('2010-10-20 10:20:28.999998', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setMilliMicro()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->setMilliMicro(1);
        $this->assertSame('2010-10-20 10:20:30.000001', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->setMilliMicro(876543);
        $this->assertSame('2010-10-20 10:20:30.876543', $next->format('Y-m-d H:i:s.u'));
        $next = $date->setMilliMicro(1876545);
        $this->assertSame('2010-10-20 10:20:31.876545', $next->format('Y-m-d H:i:s.u'));

        $last = $date->setMilliMicro(-1);
        $this->assertSame('2010-10-20 10:20:29.999999', $last->format('Y-m-d H:i:s.u'));
        $last = $date->setMilliMicro(-123456);
        $this->assertSame('2010-10-20 10:20:29.876544', $last->format('Y-m-d H:i:s.u'));
        $last = $date->setMilliMicro(-1123458);
        $this->assertSame('2010-10-20 10:20:28.876542', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_getMilliMicro()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date        = DateTime::now();
        $milli_micro = $date->getMilliMicro();
        $this->assertIsInt($milli_micro);
        $this->assertSame(123456, $milli_micro);


        DateTime::setTestNow('2010-10-20 10:20:30.123');

        $date        = DateTime::now();
        $milli_micro = $date->getMilliMicro();
        $this->assertIsInt($milli_micro);
        $this->assertSame(123000, $milli_micro);
    }

    public function test_addMilli()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMilli(1);
        $this->assertSame('2010-10-20 10:20:30.124456', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMilli(876);
        $this->assertSame('2010-10-20 10:20:30.999456', $next->format('Y-m-d H:i:s.u'));
        $next = $date->addMilli(878);
        $this->assertSame('2010-10-20 10:20:31.001456', $next->format('Y-m-d H:i:s.u'));
        $next = $date->addMilli(1878);
        $this->assertSame('2010-10-20 10:20:32.001456', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addMilli(-1);
        $this->assertSame('2010-10-20 10:20:30.122456', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMilli(-123);
        $this->assertSame('2010-10-20 10:20:30.000456', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMilli(-124);
        $this->assertSame('2010-10-20 10:20:29.999456', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMilli(-1125);
        $this->assertSame('2010-10-20 10:20:28.998456', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setMilli()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->setMilli(1);
        $this->assertSame('2010-10-20 10:20:30.001456', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->setMilli(876);
        $this->assertSame('2010-10-20 10:20:30.876456', $next->format('Y-m-d H:i:s.u'));
        $next = $date->setMilli(1876);
        $this->assertSame('2010-10-20 10:20:31.876456', $next->format('Y-m-d H:i:s.u'));

        $last = $date->setMilli(-1);
        $this->assertSame('2010-10-20 10:20:29.999456', $last->format('Y-m-d H:i:s.u'));
        $last = $date->setMilli(-123);
        $this->assertSame('2010-10-20 10:20:29.877456', $last->format('Y-m-d H:i:s.u'));
        $last = $date->setMilli(-1123);
        $this->assertSame('2010-10-20 10:20:28.877456', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_getMilli()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date  = DateTime::now();
        $milli = $date->getMilli();
        $this->assertIsInt($milli);
        $this->assertSame(123, $milli);


        DateTime::setTestNow('2010-10-20 10:20:30.1');

        $date  = DateTime::now();
        $milli = $date->getMilli();
        $this->assertIsInt($milli);
        $this->assertSame(100, $milli);
    }

    public function test_addMicro()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMicro(1);
        $this->assertSame('2010-10-20 10:20:30.123457', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addMicro(876543);
        $this->assertSame('2010-10-20 10:20:30.999999', $next->format('Y-m-d H:i:s.u'));
        $next = $date->addMicro(876545);
        $this->assertSame('2010-10-20 10:20:31.000001', $next->format('Y-m-d H:i:s.u'));
        $next = $date->addMicro(1876545);
        $this->assertSame('2010-10-20 10:20:32.000001', $next->format('Y-m-d H:i:s.u'));

        $last = $date->addMicro(-1);
        $this->assertSame('2010-10-20 10:20:30.123455', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMicro(-456);
        $this->assertSame('2010-10-20 10:20:30.123000', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMicro(-457);
        $this->assertSame('2010-10-20 10:20:30.122999', $last->format('Y-m-d H:i:s.u'));
        $last = $date->addMicro(-1458);
        $this->assertSame('2010-10-20 10:20:30.121998', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_setMicro()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->setMicro(1);
        $this->assertSame('2010-10-20 10:20:30.123001', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.123456', $date->format('Y-m-d H:i:s.u'));
        $next = $date->setMicro(543);
        $this->assertSame('2010-10-20 10:20:30.123543', $next->format('Y-m-d H:i:s.u'));
        $next = $date->setMicro(1345);
        $this->assertSame('2010-10-20 10:20:30.124345', $next->format('Y-m-d H:i:s.u'));

        $last = $date->setMicro(-1);
        $this->assertSame('2010-10-20 10:20:30.122999', $last->format('Y-m-d H:i:s.u'));
        $last = $date->setMicro(-456);
        $this->assertSame('2010-10-20 10:20:30.122544', $last->format('Y-m-d H:i:s.u'));
        $last = $date->setMicro(-1456);
        $this->assertSame('2010-10-20 10:20:30.121544', $last->format('Y-m-d H:i:s.u'));
    }

    public function test_getMicro()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date  = DateTime::now();
        $micro = $date->getMicro();
        $this->assertIsInt($micro);
        $this->assertSame(456, $micro);


        DateTime::setTestNow('2010-10-20 10:20:30.1234');
        $date  = DateTime::now();
        $micro = $date->getMicro();
        $this->assertIsInt($micro);
        $this->assertSame(400, $micro);


        DateTime::setTestNow('2010-10-20 10:20:30.123');
        $date  = DateTime::now();
        $micro = $date->getMicro();
        $this->assertIsInt($micro);
        $this->assertSame(0, $micro);


        DateTime::setTestNow('2010-10-20 10:20:30.12301');
        $date  = DateTime::now();
        $micro = $date->getMicro();
        $this->assertIsInt($micro);
        $this->assertSame(10, $micro);
    }

    public function test_getMicroTimestamp()
    {
        for ($i = 0; $i < 100; $i++) {
            $microtime = microtime(true);
            $date      = new DateTime($microtime);
            $this->assertStringStartsWith((string)$microtime, $date->format('U.u'));
            $this->assertEquals(floatval((string)$microtime), $date->getMicroTimestamp());
        }
    }

    public function test_getDayOfWeek()
    {
        $this->assertEquals(DayOfWeek::WEDNESDAY(), DateTime::now()->getDayOfWeek());
    }

    public function test_getMeridiem()
    {
        Translator::setLocale('en');
        $this->assertSame('AM', DateTime::createDateTime('2010-01-01 11:00:00')->getMeridiem());
        $this->assertSame('am', DateTime::createDateTime('2010-01-01 11:00:00')->getMeridiem(false));
        $this->assertSame('PM', DateTime::createDateTime('2010-01-01 12:00:00')->getMeridiem());
        $this->assertSame('pm', DateTime::createDateTime('2010-01-01 12:00:00')->getMeridiem(false));

        Translator::setLocale('ja');
        $this->assertSame('午前', DateTime::createDateTime('2010-01-01 11:00:00')->getMeridiem());
        $this->assertSame('午前', DateTime::createDateTime('2010-01-01 11:00:00')->getMeridiem(false));
        $this->assertSame('午後', DateTime::createDateTime('2010-01-01 12:00:00')->getMeridiem());
        $this->assertSame('午後', DateTime::createDateTime('2010-01-01 12:00:00')->getMeridiem(false));
    }

    public function test_convertTo()
    {
        $micro  = microtime(true);
        $now    = new DateTime($micro);
        $millis = intval(Strings::latrim($micro, '.'));

        $this->assertSame($now, $now->convertTo(DateTime::class));
        $this->assertSame($now, $now->convertTo(\DateTimeImmutable::class));

        $date = $now->convertTo(\DateTime::class);
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertSame($now->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($now->getTimezone()->getName(), $date->getTimezone()->getName());

        $this->assertSame($millis, $now->convertTo('int'));

        $this->assertEquals(floatval((string)$micro), $now->convertTo('float'));
    }

    public function test_format()
    {
        $this->assertSame('2010-10-20 10:20:30', DateTime::now()->format());
        $this->assertSame('2010/10/20 10:20:30', DateTime::now()->format('Y/m/d H:i:s'));
    }

    /**
     * @dataProvider dataFormatExtendeds
     */
    public function test_format_extended($locale, $expect, $datetime, $format)
    {
        Translator::setLocale($locale);
        $this->assertSame($expect, $datetime->format($format));
    }

    public function dataFormatExtendeds()
    {
        $this->setUp();
        DateTime::setTestNow('2010-10-20 13:20:30.123456');
        $now = DateTime::now();

        return [
            ['en', '2010-10-20(x3) 13:20:30', $now, 'Y-m-d(\xw) H:i:s'],
            ['en', '2010-10-20(x33) 13:20:30', $now, 'Y-m-d(\xww) H:i:s'],
            ['en', '2010-10-20(x333) 13:20:30', $now, 'Y-m-d(\xwww) H:i:s'],
            ['en', '2010-10-20(xw) 13:20:30', $now, 'Y-m-d(x\w) H:i:s'],

            // en
            ['en', '2010-10-20(We) 13:20:30', $now, 'Y-m-d(xw) H:i:s'],
            ['en', '2010-10-20(Wed) 13:20:30', $now, 'Y-m-d(xww) H:i:s'],
            ['en', '2010-10-20(Wednesday) 13:20:30', $now, 'Y-m-d(xwww) H:i:s'],

            ['en', '2010-Oct-20 13:20:30', $now, 'Y-xmm-d H:i:s'],
            ['en', '2010-October-20 13:20:30', $now, 'Y-xmmm-d H:i:s'],

            ['en', '2010-10-20 am 11:00:00', DateTime::createDateTime('2010-10-20 11:00:00'), 'Y-m-d xa H:i:s'],
            ['en', '2010-10-20 AM 11:00:00', DateTime::createDateTime('2010-10-20 11:00:00'), 'Y-m-d xA H:i:s'],
            ['en', '2010-10-20 pm 12:00:00', DateTime::createDateTime('2010-10-20 12:00:00'), 'Y-m-d xa H:i:s'],
            ['en', '2010-10-20 PM 12:00:00', DateTime::createDateTime('2010-10-20 12:00:00'), 'Y-m-d xA H:i:s'],

            ['en', '13:20', $now, 'Xt'],
            ['en', '13:20:30', $now, 'Xtt'],
            ['en', '13:20:30.123456', $now, 'Xttt'],
            ['en', '20/10/2010', $now, 'Xd'],
            ['en', '20 October 2010', $now, 'Xdd'],
            ['en', 'Wednesday, 20 October 2010', $now, 'Xddd'],
            ['en', 'Wednesday, 20 October 2010 13:20:30', $now, 'Xddd Xtt'],
            ['en', 'Wednesday, 20 October 2010 13:20:30 UTC [PM]', $now, 'Xddd Xtt e [xA]'],

            // ja
            ['ja', '2010-10-20(水) 13:20:30', $now, 'Y-m-d(xw) H:i:s'],
            ['ja', '2010-10-20(水) 13:20:30', $now, 'Y-m-d(xww) H:i:s'],
            ['ja', '2010-10-20(水曜日) 13:20:30', $now, 'Y-m-d(xwww) H:i:s'],

            ['ja', '2010-10月-20 13:20:30', $now, 'Y-xmm-d H:i:s'],
            ['ja', '2010-10月-20 13:20:30', $now, 'Y-xmmm-d H:i:s'],

            ['ja', '2010-10-20 午前 11:00:00', DateTime::createDateTime('2010-10-20 11:00:00'), 'Y-m-d xa H:i:s'],
            ['ja', '2010-10-20 午前 11:00:00', DateTime::createDateTime('2010-10-20 11:00:00'), 'Y-m-d xA H:i:s'],
            ['ja', '2010-10-20 午後 12:00:00', DateTime::createDateTime('2010-10-20 12:00:00'), 'Y-m-d xa H:i:s'],
            ['ja', '2010-10-20 午後 12:00:00', DateTime::createDateTime('2010-10-20 12:00:00'), 'Y-m-d xA H:i:s'],

            ['ja', '13:20', $now, 'Xt'],
            ['ja', '13:20:30', $now, 'Xtt'],
            ['ja', '13:20:30.123456', $now, 'Xttt'],
            ['ja', '2010/10/20', $now, 'Xd'],
            ['ja', '2010年10月20日', $now, 'Xdd'],
            ['ja', '2010年10月20日(水)', $now, 'Xddd'],
            ['ja', '2010年10月20日(水) 13:20:30', $now, 'Xddd Xtt'],
            ['ja', '2010年10月20日(水) 13:20:30 UTC [午後]', $now, 'Xddd Xtt e [xA]'],


            ['non', '2010-10-20(We) 13:20:30', $now, 'Y-m-d(xw) H:i:s'],
            ['non', '2010-10-20(Wed) 13:20:30', $now, 'Y-m-d(xww) H:i:s'],
            ['non', '2010-10-20(Wednesday) 13:20:30', $now, 'Y-m-d(xwww) H:i:s'],
        ];
    }

    public function test_i18n()
    {
        $i18n_dir = Path::normalize(App::path('../../src/Rebet/Tools/DateTime/i18n'));
        $locales  = array_diff(scandir($i18n_dir), ['.', '..']);
        $datetime = new DateTime('2019-01-06 13:20:30.123456', 'UTC');
        foreach ($locales as $locale) {
            Translator::setLocale($locale);
            $resource = Resource::load('php', "{$i18n_dir}/{$locale}/datetime.php");

            foreach ([
                'label'       => 'xmmm',
                'label_short' => 'xmm',
            ] as $key => $format) {
                foreach ($resource[Month::class][$key] as $month => $label) {
                    if ($month === 0) {
                        continue;
                    }
                    $test_at = $datetime->addMonth($month - 1);
                    $this->assertSame($label, $test_at->format($format));
                }
            }

            foreach ([
                'label'       => 'xwww',
                'label_short' => 'xww',
                'label_min'   => 'xw',
            ] as $key => $format) {
                foreach ($resource[DayOfWeek::class][$key] as $day_of_week => $label) {
                    $test_at = $datetime->addDay($day_of_week);
                    $this->assertSame($label, $test_at->format($format));
                }
            }

            $meridiem = $resource['@meridiem'] ?? null;
            $this->assertNotNull($meridiem);
            foreach (range(0, 23) as $hour) {
                $test_at = $datetime->addHour($hour);
                $this->assertSame($meridiem($test_at, true), $test_at->format('xA'), "{$test_at} in {$locale}");
                $this->assertSame($meridiem($test_at, false), $test_at->format('xa'), "{$test_at} in {$locale}");
            }

            $formats = $resource['@formats'];
            $this->assertFalse(empty($formats));
            foreach ($formats as $localized_format => $real_format) {
                foreach (range(0, 2) as $month) {
                    foreach (range(0, 2) as $day_of_week) {
                        foreach (range(0, 23, 4) as $hour) {
                            $test_at = $datetime->addMonth($month)->addDay($day_of_week)->addHour($hour);
                            $this->assertSame($test_at->format($real_format), $test_at->format($localized_format), "{$localized_format} => {$real_format} at {$test_at} in {$locale}");
                        }
                    }
                }
            }
        }
    }

    public function test_age()
    {
        $this->assertSame(9, DateTime::createDateTime('2000-10-21')->age());
        $this->assertSame(10, DateTime::createDateTime('2000-10-20')->age());
        $this->assertSame(10, DateTime::createDateTime('2000-10-19')->age());
        $this->assertSame(10, DateTime::createDateTime('1999-10-21')->age());
        $this->assertSame(11, DateTime::createDateTime('1999-10-20')->age());
        $this->assertSame(11, DateTime::createDateTime('1999-10-19')->age());

        $this->assertSame(19, DateTime::createDateTime('2000-10-21')->age('2020-10-20'));
        $this->assertSame(20, DateTime::createDateTime('2000-10-20')->age('2020-10-20'));
        $this->assertSame(20, DateTime::createDateTime('2000-10-19')->age('2020-10-20'));
        $this->assertSame(20, DateTime::createDateTime('1999-10-21')->age('2020-10-20'));
        $this->assertSame(21, DateTime::createDateTime('1999-10-20')->age('2020-10-20'));
        $this->assertSame(21, DateTime::createDateTime('1999-10-19')->age('2020-10-20'));
    }

    public function test_startsOfYear()
    {
        $this->assertSame('2010-01-01 00:00:00.000000', DateTime::now()->startsOfYear()->format('Y-m-d H:i:s.u'));
    }

    public function test_endsOfYear()
    {
        $this->assertSame('2010-12-31 23:59:59.999999', DateTime::now()->endsOfYear()->format('Y-m-d H:i:s.u'));
    }

    public function test_startsOfMonth()
    {
        $this->assertSame('2010-10-01 00:00:00.000000', DateTime::now()->startsOfMonth()->format('Y-m-d H:i:s.u'));
    }

    public function test_endsOfMonth()
    {
        $this->assertSame('2010-10-31 23:59:59.999999', DateTime::now()->endsOfMonth()->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-12-31 23:59:59.999999', DateTime::createDateTime('2010-12-20 12:34:56.123456')->endsOfMonth()->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-11-30 23:59:59.999999', DateTime::createDateTime('2010-11-20 12:34:56.123456')->endsOfMonth()->format('Y-m-d H:i:s.u'));
        $this->assertSame('2020-02-29 23:59:59.999999', DateTime::createDateTime('2020-02-20 12:34:56.123456')->endsOfMonth()->format('Y-m-d H:i:s.u'));
        $this->assertSame('2010-02-28 23:59:59.999999', DateTime::createDateTime('2010-02-20 12:34:56.123456')->endsOfMonth()->format('Y-m-d H:i:s.u'));
    }

    public function test_startsOfDay()
    {
        $this->assertSame('2010-10-20 00:00:00.000000', DateTime::now()->startsOfDay()->format('Y-m-d H:i:s.u'));
    }

    public function test_endsOfDay()
    {
        $this->assertSame('2010-10-20 23:59:59.999999', DateTime::now()->endsOfDay()->format('Y-m-d H:i:s.u'));
    }

    public function test_startsOfHour()
    {
        $this->assertSame('2010-10-20 10:00:00.000000', DateTime::now()->startsOfHour()->format('Y-m-d H:i:s.u'));
    }

    public function test_endsOfHour()
    {
        $this->assertSame('2010-10-20 10:59:59.999999', DateTime::now()->endsOfHour()->format('Y-m-d H:i:s.u'));
    }

    public function test_startsOfMinute()
    {
        $this->assertSame('2010-10-20 10:20:00.000000', DateTime::now()->startsOfMinute()->format('Y-m-d H:i:s.u'));
    }

    public function test_endsOfMinute()
    {
        $this->assertSame('2010-10-20 10:20:59.999999', DateTime::now()->endsOfMinute()->format('Y-m-d H:i:s.u'));
    }

    public function test_startsOfSecond()
    {
        $this->assertSame('2010-10-20 10:20:30.000000', DateTime::now()->startsOfSecond()->format('Y-m-d H:i:s.u'));
    }

    public function test_endsOfSecond()
    {
        $this->assertSame('2010-10-20 10:20:30.999999', DateTime::now()->endsOfSecond()->format('Y-m-d H:i:s.u'));
    }

    public function test_startsOfWeek()
    {
        $this->assertSame('2010-10-18 00:00:00.000000', DateTime::now()->startsOfWeek()->format('Y-m-d H:i:s.u'));
    }

    public function test_endsOfWeek()
    {
        $this->assertSame('2010-10-24 23:59:59.999999', DateTime::now()->endsOfWeek()->format('Y-m-d H:i:s.u'));
    }

    /**
     * @dataProvider dataXxxxs
     */
    public function test_isXxxx(string $datetime, $xxxx, $expect)
    {
        $method = "is{$xxxx}";
        $this->assertSame($expect, DateTime::createDateTime($datetime)->$method());
    }

    public function dataXxxxs()
    {
        return [
            ['2010-10-17', 'Sunday'   , true],
            ['2010-10-18', 'Monday'   , true],
            ['2010-10-19', 'Tuesday'  , true],
            ['2010-10-20', 'Wednesday', true],
            ['2010-10-21', 'Thursday' , true],
            ['2010-10-22', 'Friday'   , true],
            ['2010-10-23', 'Saturday' , true],

            ['2010-10-18', 'Sunday'   , false],
            ['2010-10-19', 'Monday'   , false],
            ['2010-10-20', 'Tuesday'  , false],
            ['2010-10-21', 'Wednesday', false],
            ['2010-10-22', 'Thursday' , false],
            ['2010-10-23', 'Friday'   , false],
            ['2010-10-17', 'Saturday' , false],

            ['2010-10-17', 'Weekends', true ],
            ['2010-10-18', 'Weekends', false],
            ['2010-10-19', 'Weekends', false],
            ['2010-10-20', 'Weekends', false],
            ['2010-10-21', 'Weekends', false],
            ['2010-10-22', 'Weekends', false],
            ['2010-10-23', 'Weekends', true ],

            ['2010-10-17', 'Weekdays', false],
            ['2010-10-18', 'Weekdays', true ],
            ['2010-10-19', 'Weekdays', true ],
            ['2010-10-20', 'Weekdays', true ],
            ['2010-10-21', 'Weekdays', true ],
            ['2010-10-22', 'Weekdays', true ],
            ['2010-10-23', 'Weekdays', false],
        ];
    }
}

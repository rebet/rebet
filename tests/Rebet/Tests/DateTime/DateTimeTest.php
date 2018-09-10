<?php
namespace Rebet\Tests\DateTime;

use Rebet\Tests\RebetTestCase;
use Rebet\DateTime\DateTime;
use Rebet\DateTime\DateTimeZone;
use Rebet\Config\Config;
use Rebet\Config\App;

class DateTimeTest extends RebetTestCase {
    public function setUp() {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30', 'UTC');
    }

    public function test_setTestNow() {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('2010-10-20 10:20:30', DateTime::getTestNow());
        $this->assertSame('UTC', DateTime::getTestNowTimezone());

        DateTime::setTestNow('2010-10-20 10:20:30.12345', 'Asia/Tokyo');
        $this->assertSame('2010-10-20 10:20:30.12345', DateTime::getTestNow());
        $this->assertSame('Asia/Tokyo', DateTime::getTestNowTimezone());
    }

    public function test_getTestNow() {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('2010-10-20 10:20:30', DateTime::getTestNow());

        DateTime::setTestNow('2010-10-20 10:20:30.12345');
        $this->assertSame('2010-10-20 10:20:30.12345', DateTime::getTestNow());
    }

    public function test_setTestNowTimezone() {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('UTC', DateTime::getTestNowTimezone());

        DateTime::setTestNow('2010-10-20 10:20:30.12345', 'Asia/Tokyo');
        $this->assertSame('Asia/Tokyo', DateTime::getTestNowTimezone());
    }
    
    public function test_removeTestNow() {
        DateTime::setTestNow('2010-10-20 10:20:30');
        $this->assertSame('2010-10-20 10:20:30', DateTime::getTestNow());
        DateTime::removeTestNow();
        $this->assertNull(DateTime::getTestNow());
        $this->assertNull(DateTime::getTestNowTimezone());
    }

    public function test_construct() {
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
        $date = new DateTime($org_date);
        $this->assertSame('2010-10-20 10:20:30', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());

        $date = new DateTime($org_date, 'UTC');
        $this->assertSame('2010-10-20 01:20:30', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }

    public function test_createFromFormat() {
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

    public function test_analyzeDateTime() {
        DateTime::setTestNow('2010-10-20 01:02:03.456789');
        
        $input = '2010-10-20 12:34:56';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y-m-d H:i:s', $apply_format);
        $this->assertSame('2010-10-20 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
        
        $input = '2010年10月20日 12:34';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y年m月d日 H:i', $apply_format);
        $this->assertSame('2010-10-20 12:34:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
        
        $input = '20101020';
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
        
        $input = new DateTime();
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertEquals($input, $date);
        $this->assertSame(DateTime::config('default_format'), $apply_format);
        
        $input = new \DateTime('2010-10-20 01:02:03.456789');
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        $this->assertSame(DateTime::config('default_format'), $apply_format);
        
        $input = new \DateTimeImmutable('2010-10-20 01:02:03.456789');
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        $this->assertSame(DateTime::config('default_format'), $apply_format);
        
        $input = '2010.10.20';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertNull($date);
        $this->assertNull($apply_format);
        
        [$date, $apply_format] = DateTime::analyzeDateTime($input, ['Y.m.d']);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y.m.d', $apply_format);
        $this->assertSame('2010-10-20 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
        
        $input = '10/01, 2010';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertNull($date);
        $this->assertNull($apply_format);
        
        [$date, $apply_format] = DateTime::analyzeDateTime($input, ['Y.m.d', 'm/d, Y']);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('m/d, Y', $apply_format);
        $this->assertSame('2010-10-01 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
        
        $input = '2010-10-20 12:34:56';
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
        
        $input = '2010 01 02';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y m d', $apply_format);
        $this->assertSame('2010-01-02 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
    }
    
    public function test_createDateTime() {
        DateTime::setTestNow('2010-10-20 01:02:03.456789');
        
        $input = '2010-10-20 12:34:56';
        $date = DateTime::createDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2010-10-20 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
    }
    
    public function test_add() {
        $date = new DateTime();
        $new = $date->add(new \DateInterval('P1D'));
        $this->assertInstanceOf(DateTime::class, $new);
    }
    
    public function test_modify() {
        $date = new DateTime();
        $new = $date->modify('+1 day');
        $this->assertInstanceOf(DateTime::class, $new);
    }
    
    public function test_setDate() {
        $date = new DateTime();
        $new = $date->setDate(2011, 11, 12);
        $this->assertInstanceOf(DateTime::class, $new);
    }
    
    public function test_setISODate() {
        $date = new DateTime();
        $new = $date->setISODate(2010, 1);
        $this->assertInstanceOf(DateTime::class, $new);
    }
    
    public function test_setTime() {
        $date = new DateTime();
        $new = $date->setTime(10, 11);
        $this->assertInstanceOf(DateTime::class, $new);
    }
    
    public function test_setTimestamp() {
        $date = new DateTime();
        $new = $date->setTimestamp(time());
        $this->assertInstanceOf(DateTime::class, $new);
    }
    
    public function test_setTimezone() {
        $date = new DateTime();
        $new = $date->setTimezone('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('Asia/Tokyo', $new->getTimezone()->getName());
        
        $date = new DateTime();
        $new = $date->setTimezone(new DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('Asia/Tokyo', $new->getTimezone()->getName());
        
        $date = new DateTime();
        $new = $date->setTimezone(new \DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('Asia/Tokyo', $new->getTimezone()->getName());
    }
    
    public function test_sub() {
        $date = new DateTime();
        $new = $date->sub(new \DateInterval('P1D'));
        $this->assertInstanceOf(DateTime::class, $new);
    }
    
    public function test_toString() {
        $date = new DateTime();
        $this->assertSame('2010-10-20 10:20:30', "{$date}");
    }
    
    public function test_now() {
        $now = DateTime::now();
        $this->assertInstanceOf(DateTime::class, $now);
        $this->assertSame('UTC', $now->getTimezone()->getName());
        $this->assertSame('2010-10-20 10:20:30.000000', $now->format('Y-m-d H:i:s.u'));
        
        $now = DateTime::now('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $now);
        $this->assertSame('Asia/Tokyo', $now->getTimezone()->getName());
        $this->assertSame('2010-10-20 19:20:30.000000', $now->format('Y-m-d H:i:s.u'));
    }
    
    public function test_today() {
        $today = DateTime::today();
        $this->assertInstanceOf(DateTime::class, $today);
        $this->assertSame('UTC', $today->getTimezone()->getName());
        $this->assertSame('2010-10-20 00:00:00.000000', $today->format('Y-m-d H:i:s.u'));
        
        $today = DateTime::today('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $today);
        $this->assertSame('Asia/Tokyo', $today->getTimezone()->getName());
        $this->assertSame('2010-10-20 00:00:00.000000', $today->format('Y-m-d H:i:s.u'));
    }
    
    public function test_yesterday() {
        $yesterday = DateTime::yesterday();
        $this->assertInstanceOf(DateTime::class, $yesterday);
        $this->assertSame('UTC', $yesterday->getTimezone()->getName());
        $this->assertSame('2010-10-19 00:00:00.000000', $yesterday->format('Y-m-d H:i:s.u'));
        
        $yesterday = DateTime::yesterday('Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $yesterday);
        $this->assertSame('Asia/Tokyo', $yesterday->getTimezone()->getName());
        $this->assertSame('2010-10-19 00:00:00.000000', $yesterday->format('Y-m-d H:i:s.u'));
    }
    
    public function test_addYear() {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $next = $date->addYear(1);
        $this->assertSame('2011-10-20 10:20:30.000000', $next->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $next);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));

        $last = $date->addYear(-1);
        $this->assertSame('2009-10-20 10:20:30.000000', $last->format('Y-m-d H:i:s.u'));
    }
    
    public function test_setYear() {
        $date = DateTime::now();
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
        $new = $date->setYear(2011);
        $this->assertSame('2011-10-20 10:20:30.000000', $new->format('Y-m-d H:i:s.u'));
        $this->assertInstanceOf(DateTime::class, $new);
        $this->assertSame('2010-10-20 10:20:30.000000', $date->format('Y-m-d H:i:s.u'));
    }
    
    public function test_getYear() {
        $date = DateTime::now();
        $year = $date->getYear();
        $this->assertInternalType(\int::class, $year);
        $this->assertSame(2010, $year);
    }
    
    public function test_addMonth() {
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
    
    public function test_setMonth() {
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
    
    public function test_getMonth() {
        $date = DateTime::now();
        $month = $date->getMonth();
        $this->assertInternalType(\int::class, $month);
        $this->assertSame(10, $month);
    }
    
    public function test_addDay() {
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
    
    public function test_setDay() {
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
    
    public function test_getDay() {
        $date = DateTime::now();
        $day = $date->getDay();
        $this->assertInternalType(\int::class, $day);
        $this->assertSame(20, $day);
    }
    
    public function test_addHour() {
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
    
    public function test_setHour() {
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
    
    public function test_getHour() {
        $date = DateTime::now();
        $hour = $date->getHour();
        $this->assertInternalType(\int::class, $hour);
        $this->assertSame(10, $hour);
    }
    
    public function test_addMinute() {
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
    
    public function test_setMinute() {
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
    
    public function test_getMinute() {
        $date = DateTime::now();
        $minute = $date->getMinute();
        $this->assertInternalType(\int::class, $minute);
        $this->assertSame(20, $minute);
    }
    
    public function test_addSecond() {
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
    
    public function test_setSecond() {
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
    
    public function test_getSecond() {
        $date = DateTime::now();
        $second = $date->getSecond();
        $this->assertInternalType(\int::class, $second);
        $this->assertSame(30, $second);
    }

    public function test_addMilliMicro() {
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
    
    public function test_setMilliMicro() {
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
    
    public function test_getMilliMicro() {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $milli_micro = $date->getMilliMicro();
        $this->assertInternalType(\int::class, $milli_micro);
        $this->assertSame(123456, $milli_micro);


        DateTime::setTestNow('2010-10-20 10:20:30.123');

        $date = DateTime::now();
        $milli_micro = $date->getMilliMicro();
        $this->assertInternalType(\int::class, $milli_micro);
        $this->assertSame(123000, $milli_micro);
    }

    public function test_addMilli() {
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
    
    public function test_setMilli() {
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
    
    public function test_getMilli() {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $milli = $date->getMilli();
        $this->assertInternalType(\int::class, $milli);
        $this->assertSame(123, $milli);

        
        DateTime::setTestNow('2010-10-20 10:20:30.1');

        $date = DateTime::now();
        $milli = $date->getMilli();
        $this->assertInternalType(\int::class, $milli);
        $this->assertSame(100, $milli);
    }    

    public function test_addMicro() {
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
    
    public function test_setMicro() {
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
    
    public function test_getMicro() {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');

        $date = DateTime::now();
        $micro = $date->getMicro();
        $this->assertInternalType(\int::class, $micro);
        $this->assertSame(456, $micro);


        DateTime::setTestNow('2010-10-20 10:20:30.1234');
        $date = DateTime::now();
        $micro = $date->getMicro();
        $this->assertInternalType(\int::class, $micro);
        $this->assertSame(400, $micro);


        DateTime::setTestNow('2010-10-20 10:20:30.123');
        $date = DateTime::now();
        $micro = $date->getMicro();
        $this->assertInternalType(\int::class, $micro);
        $this->assertSame(0, $micro);


        DateTime::setTestNow('2010-10-20 10:20:30.12301');
        $date = DateTime::now();
        $micro = $date->getMicro();
        $this->assertInternalType(\int::class, $micro);
        $this->assertSame(10, $micro);
    }
}

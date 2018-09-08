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
        DateTime::setTestNow('2010-10-10 00:00:00');
    }

    public function test_setTestNow() {
        DateTime::setTestNow('2010-10-10 00:00:00');
        $this->assertSame('2010-10-10 00:00:00', DateTime::getTestNow());

        DateTime::setTestNow('2010-10-10 00:00:00.12345');
        $this->assertSame('2010-10-10 00:00:00.12345', DateTime::getTestNow());
    }

    public function test_getTestNow() {
        DateTime::setTestNow('2010-10-10 00:00:00');
        $this->assertSame('2010-10-10 00:00:00', DateTime::getTestNow());

        DateTime::setTestNow('2010-10-10 00:00:00.12345');
        $this->assertSame('2010-10-10 00:00:00.12345', DateTime::getTestNow());
    }

    public function test_removeTestNow() {
        DateTime::setTestNow('2010-10-10 00:00:00');
        $this->assertSame('2010-10-10 00:00:00', DateTime::getTestNow());
        DateTime::removeTestNow();
        $this->assertNull(DateTime::getTestNow());
    }

    public function test_construct() {
        $date = new DateTime();
        $this->assertSame('2010-10-10 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('2010-10-10 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('yesterday');
        $this->assertSame('2010-10-09 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('noon');
        $this->assertSame('2010-10-10 12:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('tomorrow');
        $this->assertSame('2010-10-11 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('tomorrow noon');
        $this->assertSame('2010-10-11 12:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('+1 day');
        $this->assertSame('2010-10-11 00:00:00', $date->format('Y-m-d H:i:s'));


        DateTime::setTestNow('2010-10-10 00:00:00.123456');

        $date = new DateTime();
        $this->assertSame('2010-10-10 00:00:00.123456', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('+1 day +2 hour');
        $this->assertSame('2010-10-11 02:00:00.123456', $date->format('Y-m-d H:i:s.u'));


        DateTime::setTestNow('2010-10-10 00:00');

        $date = new DateTime();
        $this->assertSame('2010-10-10 00:00', $date->format('Y-m-d H:i'));
        $this->assertSame('2010-10-10 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));


        DateTime::setTestNow('2010-10-10 00:00:00');

        $date = new DateTime();
        $this->assertSame('2010-10-10 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('2010-10-10 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));

        $date = new DateTime('now', 'Asia/Tokyo');
        $this->assertSame('2010-10-10 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('now', new \DateTimeZone('Asia/Tokyo'));
        $this->assertSame('2010-10-10 00:00:00', $date->format('Y-m-d H:i:s'));

        $date = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
        $this->assertSame('2010-10-10 00:00:00', $date->format('Y-m-d H:i:s'));

        $org_date = \DateTime::createFromFormat('Y-m-d H:i:s', '2010-10-10 00:00:00', new \DateTimeZone('Asia/Tokyo'));
        $date = new DateTime($org_date);
        $this->assertSame('2010-10-10 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());

        $date = new DateTime($org_date, 'UTC');
        $this->assertSame('2010-10-09 15:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }

    public function test_createFromFormat() {
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-10 00:00:00.123456');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2010-10-10 00:00:00.123456', $date->format('Y-m-d H:i:s.u'));
        
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', null);
        $this->assertFalse($date);
        
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '');
        $this->assertFalse($date);
        
        $input = new DateTime();
        $date  = DateTime::createFromFormat('Y-m-d H:i:s.u', $input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertEquals($input, $date);
        
        $input = new \DateTime('2010-10-10 00:00:00.123456');
        $date  = DateTime::createFromFormat('Y-m-d H:i:s.u', $input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        
        $input = new \DateTimeImmutable('2010-10-10 00:00:00.123456');
        $date  = DateTime::createFromFormat('Y-m-d H:i:s.u', $input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010/10/10');
        $this->assertFalse($date);
        
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-10 00:00:00.123456', 'Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-10 00:00:00.123456', $date->format('Y-m-d H:i:s.u'));
        
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-10 00:00:00.123456', new DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-10 00:00:00.123456', $date->format('Y-m-d H:i:s.u'));
        
        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', '2010-10-10 00:00:00.123456', new \DateTimeZone('Asia/Tokyo'));
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-10 00:00:00.123456', $date->format('Y-m-d H:i:s.u'));
    }

    public function test_analyzeDateTime() {
        DateTime::setTestNow('2010-10-10 01:02:03.456789');
        
        $input = '2010-10-10 12:34:56';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y-m-d H:i:s', $apply_format);
        $this->assertSame('2010-10-10 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
        
        $input = '2010年10月10日 12:34';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y年m月d日 H:i', $apply_format);
        $this->assertSame('2010-10-10 12:34:00.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
        
        $input = '20101010';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Ymd', $apply_format);
        $this->assertSame('2010-10-10 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
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
        
        $input = new \DateTime('2010-10-10 01:02:03.456789');
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        $this->assertSame(DateTime::config('default_format'), $apply_format);
        
        $input = new \DateTimeImmutable('2010-10-10 01:02:03.456789');
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame($input->format('Y-m-d H:i:s.u'), $date->format('Y-m-d H:i:s.u'));
        $this->assertSame(DateTime::config('default_format'), $apply_format);
        
        $input = '2010.10.10';
        [$date, $apply_format] = DateTime::analyzeDateTime($input);
        $this->assertNull($date);
        $this->assertNull($apply_format);
        
        [$date, $apply_format] = DateTime::analyzeDateTime($input, ['Y.m.d']);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('Y.m.d', $apply_format);
        $this->assertSame('2010-10-10 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
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
        
        $input = '2010-10-10 12:34:56';
        [$date, $apply_format] = DateTime::analyzeDateTime($input, [], 'Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('Y-m-d H:i:s', $apply_format);
        $this->assertSame('2010-10-10 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
        $this->assertSame($input, $date->format($apply_format));
        
        $input = new DateTime();
        $this->assertSame('UTC', $input->getTimezone()->getName());
        $this->assertSame('2010-10-10 01:02:03.456789', $input->format('Y-m-d H:i:s.u'));
        [$date, $apply_format] = DateTime::analyzeDateTime($input, [], 'Asia/Tokyo');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());
        $this->assertSame('2010-10-10 10:02:03.456789', $date->format('Y-m-d H:i:s.u'));
        
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
        DateTime::setTestNow('2010-10-10 01:02:03.456789');
        
        $input = '2010-10-10 12:34:56';
        $date = DateTime::createDateTime($input);
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('2010-10-10 12:34:56.000000', $date->format('Y-m-d H:i:s.u'));
    }
    
    public function test_add() {
        $date = new DateTime();
        $new = $date->add(new \DateInterval('P1D'));
        $this->assertInstanceOf(DateTime::class, $new);
    }
     
    public function test_toString() {
        $date = new DateTime();
        $this->assertSame('2010-10-10 00:00:00', "{$date}");
    }
}

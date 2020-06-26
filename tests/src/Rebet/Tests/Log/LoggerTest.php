<?php
namespace Rebet\Tests\Log;

use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Logger;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class LoggerTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
    }

    public function test___construct()
    {
        $this->assertInstanceOf(Logger::class, new Logger(new TestDriver('test', LogLevel::DEBUG)));
    }

    public function test_driver()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertInstanceOf(TestDriver::class, $logger->driver());
    }

    public function test_emergency()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasEmergencyRecords());
        $logger->emergency('emergency');
        $this->assertTrue($logger->driver()->hasEmergencyRecords());
    }

    public function test_alert()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasAlertRecords());
        $logger->alert('alert');
        $this->assertTrue($logger->driver()->hasAlertRecords());
    }

    public function test_critical()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasCriticalRecords());
        $logger->critical('critical');
        $this->assertTrue($logger->driver()->hasCriticalRecords());
    }

    public function test_error()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasErrorRecords());
        $logger->error('error');
        $this->assertTrue($logger->driver()->hasErrorRecords());
    }

    public function test_warning()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasWarningRecords());
        $logger->warning('warning');
        $this->assertTrue($logger->driver()->hasWarningRecords());
    }

    public function test_notice()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasNoticeRecords());
        $logger->notice('notice');
        $this->assertTrue($logger->driver()->hasNoticeRecords());
    }

    public function test_info()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasInfoRecords());
        $logger->info('info');
        $this->assertTrue($logger->driver()->hasInfoRecords());
    }

    public function test_debug()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasDebugRecords());
        $logger->debug('debug');
        $this->assertTrue($logger->driver()->hasDebugRecords());
    }

    public function test_log()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasDebugRecords());
        $logger->log(LogLevel::ERROR, 'message', [], new \Exception('exception'));
        $this->assertTrue($logger->driver()->hasErrorRecords());
        $this->assertInstanceOf(\Exception::class, $logger->driver()->getRecords()[0]['context']['exception'] ?? null);
    }

    public function test_memory()
    {
        $logger = new Logger(new TestDriver('test', LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasDebugRecords());
        $logger->memory('message');
        $this->assertTrue($logger->driver()->hasDebugRecords());
        $this->assertContains('Peak Memory', $logger->driver()->formatted());
    }
}

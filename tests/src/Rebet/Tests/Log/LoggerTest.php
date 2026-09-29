<?php

declare(strict_types=1);

namespace Rebet\Tests\Log;

use Override;
use Psr\Log\NullLogger;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Logger;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTime;

class LoggerTest extends RebetTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
    }

    public function test___construct(): void
    {
        $this->assertInstanceOf(Logger::class, new Logger(new TestDriver(LogLevel::DEBUG)));
    }

    public function test_driver(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertInstanceOf(TestDriver::class, $logger->driver());
    }

    public function test_name(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertEquals('rebet', $logger->name());
        $this->assertInstanceOf(Logger::class, $logger->name('test'));
        $this->assertEquals('test', $logger->name());

        $logger = new Logger(new NullLogger());
        $this->assertEquals(null, $logger->name());
        $this->assertInstanceOf(Logger::class, $logger->name('test'));
        $this->assertEquals(null, $logger->name());
    }

    public function test_emergency(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasEmergencyRecords());
        $logger->emergency('emergency');
        $this->assertTrue($logger->driver()->hasEmergencyRecords());
    }

    public function test_alert(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasAlertRecords());
        $logger->alert('alert');
        $this->assertTrue($logger->driver()->hasAlertRecords());
    }

    public function test_critical(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasCriticalRecords());
        $logger->critical('critical');
        $this->assertTrue($logger->driver()->hasCriticalRecords());
    }

    public function test_error(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasErrorRecords());
        $logger->error('error');
        $this->assertTrue($logger->driver()->hasErrorRecords());
    }

    public function test_warning(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasWarningRecords());
        $logger->warning('warning');
        $this->assertTrue($logger->driver()->hasWarningRecords());
    }

    public function test_notice(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasNoticeRecords());
        $logger->notice('notice');
        $this->assertTrue($logger->driver()->hasNoticeRecords());
    }

    public function test_info(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasInfoRecords());
        $logger->info('info');
        $this->assertTrue($logger->driver()->hasInfoRecords());
    }

    public function test_debug(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasDebugRecords());
        $logger->debug('debug');
        $this->assertTrue($logger->driver()->hasDebugRecords());
    }

    public function test_log(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasDebugRecords());
        $logger->log(LogLevel::ERROR, 'message', [], new \Exception('exception'));
        $this->assertTrue($logger->driver()->hasErrorRecords());
        $this->assertInstanceOf(\Exception::class, $logger->driver()->getRecords()[0]['context']['exception'] ?? null);
    }

    public function test_memory(): void
    {
        $logger = new Logger(new TestDriver(LogLevel::DEBUG));
        $this->assertFalse($logger->driver()->hasDebugRecords());
        $logger->memory('message');
        $this->assertTrue($logger->driver()->hasDebugRecords());
        $this->assertStringContainsString('Peak Memory', $logger->driver()->formatted());
    }
}

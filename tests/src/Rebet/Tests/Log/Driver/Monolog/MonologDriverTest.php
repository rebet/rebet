<?php
namespace Rebet\Tests\Log\Driver\Monolog;

use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\ProcessIdProcessor;
use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\MonologDriver;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class MonologDriverTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
    }

    public function test___construct()
    {
        $driver = new MonologDriver('web', LogLevel::DEBUG);
        $this->assertInstanceOf(MonologDriver::class, $driver);
        $this->assertInstanceOf(ProcessIdProcessor::class, $driver->popProcessor());
    }

    public function test_addRecord()
    {
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
        $handler = new TestHandler();
        $driver  = new MonologDriver('test', LogLevel::DEBUG, [$handler]);
        $driver->addRecord(MonologLogger::DEBUG, "TEST");
        $datetime = $handler->getRecords()[0]['datetime'] ?? null ;
        $this->assertNotNull($datetime);
        $this->assertSame('2010-10-20 10:20:30.123456', $datetime->format('Y-m-d H:i:s.u'));
    }
}

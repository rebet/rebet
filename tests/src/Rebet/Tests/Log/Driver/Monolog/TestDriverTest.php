<?php
namespace Rebet\Tests\Log\Driver\Monolog;

use Monolog\Handler\TestHandler;
use Monolog\Processor\ProcessIdProcessor;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class TestDriverTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
    }

    public function test___construct()
    {
        $driver = new TestDriver('web', LogLevel::DEBUG);
        $this->assertInstanceOf(TestDriver::class, $driver);

        $this->assertInstanceOf(ProcessIdProcessor::class, $driver->popProcessor());
        $handler = $driver->popHandler();
        $this->assertInstanceOf(TestHandler::class, $handler);
    }

    public function test___call()
    {
        $driver = new TestDriver('web', LogLevel::DEBUG);
        $this->assertFalse($driver->hasDebugRecords());
        $driver->log(LogLevel::DEBUG, 'TEST');
        $this->assertTRue($driver->hasDebugRecords());
    }

    public function test_formatted()
    {
        $process_id = getmypid();
        $driver     = new TestDriver('web', LogLevel::DEBUG);
        $driver->log(LogLevel::DEBUG, 'Line 1');
        $driver->log(LogLevel::INFO, 'Line 2');
        $this->assertSame(
            <<<EOS
2010-10-20 10:20:30.123456 web/{$process_id} [DEBUG] Line 1
2010-10-20 10:20:30.123456 web/{$process_id} [INFO] Line 2

EOS
            ,
            $driver->formatted()
        );
    }
}

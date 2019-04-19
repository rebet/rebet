<?php
namespace Rebet\Tests\Log\Driver\Monolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\UidProcessor;
use Rebet\Common\Reflector;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
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

    public function test_processor()
    {
        $this->assertInstanceOf(ProcessIdProcessor::class, MonologDriver::processor(ProcessIdProcessor::class));
        $this->assertInstanceOf(ProcessIdProcessor::class, MonologDriver::processor(ProcessIdProcessor::class));

        $processor = MonologDriver::processor(UidProcessor::class);
        $this->assertInstanceOf(UidProcessor::class, $processor);
        $this->assertSame(7, mb_strlen($processor->getUid()));

        $processor = MonologDriver::processor(UidProcessor::class, [9]);
        $this->assertInstanceOf(UidProcessor::class, $processor);
        $this->assertSame(9, mb_strlen($processor->getUid()));

        $processor = MonologDriver::processor(UidProcessor::class, ['length' => 9]);
        $this->assertInstanceOf(UidProcessor::class, $processor);
        $this->assertSame(9, mb_strlen($processor->getUid()));

        Config::application([
            MonologDriver::class => [
                'processors' => [
                    UidProcessor::class => [
                        'length' => 12
                    ]
                ]
            ]
        ]);

        $processor = MonologDriver::processor(UidProcessor::class);
        $this->assertInstanceOf(UidProcessor::class, $processor);
        $this->assertSame(12, mb_strlen($processor->getUid()));

        $processor = MonologDriver::processor(UidProcessor::class, [9]);
        $this->assertInstanceOf(UidProcessor::class, $processor);
        $this->assertSame(9, mb_strlen($processor->getUid()));

        $processor = MonologDriver::processor(UidProcessor::class, ['length' => 9]);
        $this->assertInstanceOf(UidProcessor::class, $processor);
        $this->assertSame(9, mb_strlen($processor->getUid()));
    }

    public function test_formatter()
    {
        $this->assertInstanceOf(TextFormatter::class, MonologDriver::formatter(TextFormatter::class));
        $this->assertInstanceOf(LineFormatter::class, MonologDriver::formatter(LineFormatter::class));

        $formatter = MonologDriver::formatter(LineFormatter::class);
        $this->assertInstanceOf(LineFormatter::class, $formatter);
        $this->assertSame(LineFormatter::SIMPLE_FORMAT, Reflector::get($formatter, 'format', null, true));
        $this->assertSame(LineFormatter::SIMPLE_DATE, Reflector::get($formatter, 'dateFormat', null, true));
        $this->assertSame(null, Reflector::get($formatter, 'includeStacktraces', null, true));

        $formatter = MonologDriver::formatter(LineFormatter::class, ["[%datetime%] %level_name%: %message%\n"]);
        $this->assertInstanceOf(LineFormatter::class, $formatter);
        $this->assertSame("[%datetime%] %level_name%: %message%\n", Reflector::get($formatter, 'format', null, true));
        $this->assertSame(LineFormatter::SIMPLE_DATE, Reflector::get($formatter, 'dateFormat', null, true));
        $this->assertSame(null, Reflector::get($formatter, 'includeStacktraces', null, true));

        $formatter = MonologDriver::formatter(LineFormatter::class, ['dateFormat' => 'Y-m-d H:i:s.u']);
        $this->assertInstanceOf(LineFormatter::class, $formatter);
        $this->assertSame(LineFormatter::SIMPLE_FORMAT, Reflector::get($formatter, 'format', null, true));
        $this->assertSame('Y-m-d H:i:s.u', Reflector::get($formatter, 'dateFormat', null, true));
        $this->assertSame(null, Reflector::get($formatter, 'includeStacktraces', null, true));

        Config::application([
            MonologDriver::class => [
                'formatters' => [
                    LineFormatter::class => [
                        'format'     => "[%datetime%] %level_name%: %message%\n",
                        'dateFormat' => 'Y-m-d H:i:s.u',
                        '@after'     => function (LineFormatter $formatter) {
                            $formatter->includeStacktraces(true);
                            return $formatter;
                        },
                    ]
                ]
            ]
        ]);

        $formatter = MonologDriver::formatter(LineFormatter::class);
        $this->assertInstanceOf(LineFormatter::class, $formatter);
        $this->assertSame("[%datetime%] %level_name%: %message%\n", Reflector::get($formatter, 'format', null, true));
        $this->assertSame('Y-m-d H:i:s.u', Reflector::get($formatter, 'dateFormat', null, true));
        $this->assertSame(true, Reflector::get($formatter, 'includeStacktraces', null, true));
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

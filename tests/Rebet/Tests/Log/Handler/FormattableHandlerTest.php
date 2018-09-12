<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Log\Handler\FormattableHandler;
use Rebet\Log\Handler\LogHandler;
use Rebet\Log\LogLevel;
use Rebet\DateTime\DateTime;
use Rebet\Config\Config;
use Rebet\Config\App;

use org\bovigo\vfs\vfsStream;

class FormattableHandlerTest_NullHandler extends FormattableHandler {
    public $reported_log = null;

    public static function defaultConfig() : array {
        return [
            'log_level'     => LogLevel::INFO(),
            'log_formatter' => \Rebet\Log\Formatter\DefaultFormatter::class,
        ];
    }
    public static function setLogLevel(LogLevel $lebel) : void {
        self::setConfig(['log_level' => $lebel]);
    }
    public static function create() : LogHandler {
        return new static();
    }
    protected function report(DateTime $now, LogLevel $level, $formatted_log) : void {
        $this->reported_log = $formatted_log;
    }
    public function shutdown() : void {
        $this->reported_log = null;
    }
}

class FormattableHandlerTest extends RebetTestCase {

    private $handler;
    private $now;

    public function setUp() {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->handler = FormattableHandlerTest_NullHandler::create();
        $this->now     = DateTime::now();
    }

    public function test_create() {
        $this->assertInstanceOf(FormattableHandlerTest_NullHandler::class, FormattableHandlerTest_NullHandler::create());
    }

    public function test_handle() {
        $formatted_log = $this->handler->handle($this->now, LogLevel::TRACE(), "This is test.");
        $this->assertNull($formatted_log);

        $formatted_log = $this->handler->handle($this->now, LogLevel::DEBUG(), "This is test.");
        $this->assertNull($formatted_log);
        
        $formatted_log = $this->handler->handle($this->now, LogLevel::INFO(), "This is test.");
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->shutdown();
        
        $formatted_log = $this->handler->handle($this->now, LogLevel::WARN(), "This is test.");
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->shutdown();
        
        $formatted_log = $this->handler->handle($this->now, LogLevel::ERROR(), "This is test.");
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->shutdown();
        
        $formatted_log = $this->handler->handle($this->now, LogLevel::FATAL(), "This is test.");
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->shutdown();
    }
}
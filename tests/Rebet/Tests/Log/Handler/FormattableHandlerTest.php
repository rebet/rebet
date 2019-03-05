<?php
namespace Rebet\Tests\Log\Handler;

use Rebet\DateTime\DateTime;
use Rebet\Log\Handler\FormattableHandler;

use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class FormattableHandlerTest extends RebetTestCase
{
    private $handler;
    private $context;

    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->handler = new FormattableHandlerTest_NullHandler();
        $this->context = new LogContext(DateTime::now(), LogLevel::TRACE(), null);
    }

    public function test_constract()
    {
        $this->assertInstanceOf(FormattableHandlerTest_NullHandler::class, new FormattableHandlerTest_NullHandler());
    }

    public function test_handle()
    {
        $this->context->level   = LogLevel::TRACE();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $this->assertNull($formatted_log);

        $this->context->level   = LogLevel::DEBUG();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $this->assertNull($formatted_log);

        $this->context->level   = LogLevel::INFO();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->terminate();

        $this->context->level   = LogLevel::WARN();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->terminate();

        $this->context->level   = LogLevel::ERROR();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->terminate();

        $this->context->level   = LogLevel::FATAL();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $this->assertNotNull($formatted_log);
        $this->assertSame($formatted_log, $this->handler->reported_log);
        $this->handler->terminate();
    }
}


// ========== Mocks ==========

class FormattableHandlerTest_NullHandler extends FormattableHandler
{
    public $reported_log = null;

    public static function defaultConfig()
    {
        return [
            'log_level'     => LogLevel::INFO(),
            'log_formatter' => \Rebet\Log\Formatter\DefaultFormatter::class,
        ];
    }

    protected function report(LogContext $log, $formatted_log) : void
    {
        $this->reported_log = $formatted_log;
    }

    public function terminate() : void
    {
        $this->reported_log = null;
    }
}

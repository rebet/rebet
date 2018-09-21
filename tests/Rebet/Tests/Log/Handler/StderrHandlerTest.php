<?php
namespace Rebet\Tests\Log\Handler;

use Rebet\Tests\RebetTestCase;
use Rebet\Tests\StderrCapture;
use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;
use Rebet\Log\Handler\StderrHandler;
use Rebet\DateTime\DateTime;
use Rebet\Config\Config;
use Rebet\Config\App;

use org\bovigo\vfs\vfsStream;

class StderrHandlerTest extends RebetTestCase
{
    private $root;
    private $handler;
    private $context;

    public function setUp()
    {
        StderrCapture::clear();
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        Config::application([
            \Rebet\Log\Handler\StderrHandler::class => [
                'log_level' => LogLevel::INFO(),
            ]
        ]);

        $this->handler = new StderrHandler();
        $this->context = new LogContext(DateTime::now(), LogLevel::TRACE(), null);
    }

    public function test_construct()
    {
        $this->assertInstanceOf(StderrHandler::class, new StderrHandler());
    }

    public function test_handle_lowerLevel()
    {
        $this->context->level   = LogLevel::TRACE();
        $this->context->message = "This is test.";
        $formatted_log = $this->handler->handle($this->context);
        $this->assertNull($formatted_log);
    }

    public function test_handle_higherLevel()
    {
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = "This is test.";
        StderrCapture::start();
        $formatted_log = $this->handler->handle($this->context);
        StderrCapture::end();
        $this->assertSame($formatted_log."\n", StderrCapture::$STDERR);
    }
}

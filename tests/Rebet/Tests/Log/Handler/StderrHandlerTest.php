<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Tests\StderrCapture;
use Rebet\Log\Handler\StderrHandler;
use Rebet\Log\LogLevel;
use Rebet\DateTime\DateTime;
use Rebet\Config\Config;
use Rebet\Config\App;

use org\bovigo\vfs\vfsStream;

class StderrHandlerTest extends RebetTestCase {

    private $root;
    private $now;
    private $handler;

    public function setUp() {
        StderrCapture::clear();
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        Config::application([
            \Rebet\Log\Handler\StderrHandler::class => [
                'log_level' => LogLevel::INFO(),
            ]
        ]);

        $this->now     = DateTime::now();
        $this->handler = StderrHandler::create();
    }

    public function test_create() {
        $this->assertInstanceOf(StderrHandler::class, StderrHandler::create());
    }

    public function test_handle_lowerLevel() {
        $formatted_log = $this->handler->handle($this->now, LogLevel::TRACE(), "This is test.");
        $this->assertNull($formatted_log);
    }

    public function test_handle_higherLevel() {
        StderrCapture::start();
        $formatted_log = $this->handler->handle($this->now, LogLevel::ERROR(), "This is test.");
        StderrCapture::end();
        $this->assertSame($formatted_log."\n", StderrCapture::$STDERR);
    }
}
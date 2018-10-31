<?php
namespace Rebet\Tests\Log\Handler;

use org\bovigo\vfs\vfsStream;
use Rebet\Config\Config;

use Rebet\DateTime\DateTime;
use Rebet\Log\Handler\FileHandler;
use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;

use Rebet\Tests\RebetTestCase;

class FileHandlerTest extends RebetTestCase
{
    private $root;
    private $context;
    private $handler;
    private $file_path;

    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        Config::application([
            \Rebet\Log\Handler\FileHandler::class => [
                'log_level'     => LogLevel::INFO(),
                'log_file_path' => 'vfs://root/logs/application.log'
            ]
        ]);

        $this->context = new LogContext(DateTime::now(), LogLevel::TRACE(), null);
        $suffix        = $this->context->now->format(FileHandler::config('log_file_suffix'));
        $this->root    = vfsStream::setup();
        vfsStream::create(
            [
                'logs' => [
                    'application.log'.$suffix => '',
                ],
            ],
            $this->root
        );

        $this->handler   = new FileHandler();
        $this->file_path = FileHandler::config('log_file_path').$suffix;
    }

    public function test_construct()
    {
        $this->assertInstanceOf(FileHandler::class, new FileHandler());
    }

    public function test_handle_lowerLevel()
    {
        $this->context->level   = LogLevel::TRACE();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $reported_log           = \file_get_contents($this->file_path);
        $this->assertNull($formatted_log);
        $this->assertEmpty($reported_log);
    }

    public function test_handle_higherLevel()
    {
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = "This is test.";
        $formatted_log          = $this->handler->handle($this->context);
        $reported_log           = \file_get_contents($this->file_path);
        $this->assertSame($formatted_log."\n", $reported_log);
    }
}

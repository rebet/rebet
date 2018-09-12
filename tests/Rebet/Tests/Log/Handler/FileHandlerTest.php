<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Log\Handler\FileHandler;
use Rebet\Log\LogLevel;
use Rebet\DateTime\DateTime;
use Rebet\Config\Config;
use Rebet\Config\App;

use org\bovigo\vfs\vfsStream;

class FileHandlerTest extends RebetTestCase {

    private $root;
    private $now;
    private $handler;
    private $file_path;

    public function setUp() {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        Config::application([
            \Rebet\Log\Handler\FileHandler::class => [
                'log_level'     => LogLevel::INFO(),
                'log_file_path' => 'vfs://root/logs/application.log'
            ]
        ]);

        $this->now  = DateTime::now();
        $suffix     = $this->now->format(FileHandler::config('log_file_suffix'));
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'logs' => [
                    'application.log'.$suffix => '',
                ],
            ],
            $this->root
        );

        $this->handler   = FileHandler::create();
        $this->file_path = FileHandler::config('log_file_path').$suffix;
    }

    public function test_create() {
        $this->assertInstanceOf(FileHandler::class, FileHandler::create());
    }

    public function test_handle_lowerLevel() {
        $formatted_log = $this->handler->handle($this->now, LogLevel::TRACE(), "This is test.");
        $reported_log  = \file_get_contents($this->file_path);
        $this->assertNull($formatted_log);
        $this->assertEmpty($reported_log);
    }

    public function test_handle_higherLevel() {
        $formatted_log = $this->handler->handle($this->now, LogLevel::ERROR(), "This is test.");
        $reported_log  = \file_get_contents($this->file_path);
        $this->assertSame($formatted_log."\n", $reported_log);
    }
}
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

    public function setUp() {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        Config::application([
            \Rebet\Log\Handler\FileHandler::class => [
                'log_level'     => LogLevel::TRACE(),
                'log_file_path' => 'vfs://root/logs/application.log'
            ]
        ]);

        $now = DateTime::now();
        $suffix = FileHandler::config('log_file_suffix');
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'logs' => [
                    'application.log'.$now->format($suffix) => 'This is application.log',
                    'XxxxBatch.log'.$now->format($suffix) => '', // empty file
                ],
            ],
            $this->root
        );
    }

    public function test_create() {
        $this->assertInstanceOf(FileHandler::class, FileHandler::create());
    }

}
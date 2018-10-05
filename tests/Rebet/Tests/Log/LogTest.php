<?php
namespace Rebet\Tests\Log;

use Rebet\Tests\RebetTestCase;
use Rebet\Tests\StderrCapture;

use Rebet\Log\Log;

use Rebet\Common\System;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Foundation\App;
use Rebet\Log\LogLevel;

class LogTest extends RebetTestCase
{
    public function setUp()
    {
        System::initMock();
        Config::clear();
        App::initFrameworkConfig();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
        Log::init();
    }
    
    public function test_log()
    {
        $this->assertSameStderr(
            '',
            function () {
                Log::trace('Test');
            }
        );
        
        $this->assertContainsStderr(
            [
                '2010-10-20 10:20:30.040050',
                'ERROR',
                'Test',
            ],
            function () {
                Log::error('Test');
            }
        );
        
        $this->assertSameOutbuffer(
            '',
            function () {
                Log::terminate();
            }
        );
        
        Config::application([
            Log::class => [
                'log_middlewares' => [
                    \Rebet\Log\Middleware\WebDisplayMiddleware::class,
                ],
            ],
        ]);
        Log::init();
        
        $pid = getmypid();
        $this->assertSameStderr(
            "2010-10-20 10:20:30.040050 {$pid} [ERROR] Test log\n",
            function () {
                Log::error('Test log');
            }
        );
        
        $this->assertContainsOutbuffer(
            [
                '2010-10-20&nbsp;10:20:30.040050',
                'ERROR',
                'Test&nbsp;log',
            ],
            function () {
                Log::terminate();
            }
        );
    }
    
    public function test_log_lebel()
    {
        Config::application([
            \Rebet\Log\Handler\StderrHandler::class => [
                'log_level' => LogLevel::TRACE(),
            ],
        ]);
        
        $this->assertContainsStderr('TRACE', function () {
            Log::trace('Test');
        });
        $this->assertContainsStderr('DEBUG', function () {
            Log::debug('Test');
        });
        $this->assertContainsStderr('INFO', function () {
            Log::info('Test');
        });
        $this->assertContainsStderr('WARN', function () {
            Log::warn('Test');
        });
        $this->assertContainsStderr('ERROR', function () {
            Log::error('Test');
        });
        $this->assertContainsStderr('FATAL', function () {
            Log::fatal('Test');
        });
    }
    
    public function test_memory()
    {
        Config::application([
            \Rebet\Log\Handler\StderrHandler::class => [
                'log_level' => LogLevel::INFO(),
            ],
        ]);
        
        $this->assertContainsStderr('Memory', function () {
            Log::memory();
        });
    }
}

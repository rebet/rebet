<?php
namespace Rebet\Tests\Log;

use Rebet\Tests\RebetTestCase;
use Rebet\Tests\StderrCapture;

use Rebet\Log\Log;

use Rebet\Common\System;
use Rebet\Config\App;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Log\LogLevel;

class LogTest extends RebetTestCase
{
    public function setUp()
    {
        System::mock_init();
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
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
        
        Config::application([
            Log::class => [
                'log_middlewares' => [
                    \Rebet\Log\Middleware\WebDisplayMiddleware::class,
                ],
            ],
        ]);
        
        $pid = getmypid();
        
        Log::init();
        
        $this->assertSameStderr(
            "2010-10-20 10:20:30.040050 {$pid} [ERROR] Test log\n",
            function(){
                Log::error('Test log');
            }
        );
        
        $this->assertContainsOutbuffer(
            [
                '2010-10-20&nbsp;10:20:30.040050',
                'ERROR',
                'Test&nbsp;log',
            ],
            function(){
                Log::shutdown();
            }
        );
    }
}

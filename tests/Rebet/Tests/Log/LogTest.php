<?php
namespace Rebet\Tests\Log;

use Rebet\Config\Config;

use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Driver\NullDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class LogTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
        Config::application([
            Log::class => [
                'channels' => [
                    'test' => [
                        'driver' => TestDriver::class,
                        'name'   => 'test',
                        'level'  => LogLevel::WARNING,
                    ],
                    'stderr' => [
                        'driver' => StderrDriver::class,
                        'name'   => 'stderr',
                        'level'  => LogLevel::DEBUG,
                        'format' => "{datetime} [{channel}.{level_name}] {extra.process_id} {message}{context}{extra}{exception}\n",
                    ],
                    'missing_driver' => [
                        'name'   => 'missing_driver',
                        'level'  => LogLevel::DEBUG,
                    ]
                ]
            ]
        ]);
        Log::channel()->driver()->clear();
        Log::channel('test')->driver()->clear();
    }

    public function test_channel()
    {
        $this->assertInstanceOf(TestDriver::class, Log::channel()->driver());
        $this->assertInstanceOf(TestDriver::class, Log::channel('test')->driver());
        $this->assertInstanceOf(StderrDriver::class, Log::channel('stderr')->driver());
    }

    public function test_channel_invliad()
    {
        $process_id = getmypid();
        $this->assertSameStderr(
            "2010-10-20 10:20:30.040050 rebet/{$process_id} [WARNING] Unable to create 'nothing' channel logger. Undefined configure 'Rebet\Log\Log.channels.nothing'.\n",
            function () {
                $this->assertInstanceOf(NullDriver::class, Log::channel('nothing')->driver());
            }
        );
        $this->assertSameStderr(
            "2010-10-20 10:20:30.040050 rebet/{$process_id} [WARNING] Unable to create 'missing_driver' channel logger. Driver is undefined.\n",
            function () {
                $this->assertInstanceOf(NullDriver::class, Log::channel('missing_driver')->driver());
            }
        );
    }

    public function test_stack()
    {
        $process_id = getmypid();
        $this->assertSameStderr(
            "2010-10-20 10:20:30.040050 [stderr.ERROR] {$process_id} Somthing error happened.\n",
            function () {
                Log::stack('test', 'stderr')->error('Somthing error happened.');
            }
        );
        $this->assertSame(
            "2010-10-20 10:20:30.040050 test/{$process_id} [ERROR] Somthing error happened.\n",
            Log::channel('test')->driver()->formatted()
        );

        $this->assertSameStderr(
            "2010-10-20 10:20:30.040050 [stderr.INFO] {$process_id} Somthing infomation.\n",
            function () {
                Log::stack('test', 'stderr')->info('Somthing infomation.');
            }
        );
        $this->assertFalse(Log::channel('test')->driver()->hasInfoRecords());
    }

    public function test_log_lebel()
    {
        $driver = Log::channel()->driver();

        $driver->clear();
        Log::debug('Test');
        $this->assertContains('DEBUG', $driver->formatted());

        $driver->clear();
        Log::info('Test');
        $this->assertContains('INFO', $driver->formatted());

        $driver->clear();
        Log::notice('Test');
        $this->assertContains('NOTICE', $driver->formatted());

        $driver->clear();
        Log::warning('Test');
        $this->assertContains('WARNING', $driver->formatted());

        $driver->clear();
        Log::error('Test');
        $this->assertContains('ERROR', $driver->formatted());

        $driver->clear();
        Log::critical('Test');
        $this->assertContains('CRITICAL', $driver->formatted());

        $driver->clear();
        Log::alert('Test');
        $this->assertContains('ALERT', $driver->formatted());

        $driver->clear();
        Log::emergency('Test');
        $this->assertContains('EMERGENCY', $driver->formatted());
    }

    public function test_memory()
    {
        $driver = Log::channel()->driver();
        $driver->clear();
        Log::memory();
        $this->assertContains('Memory', $driver->formatted());
    }

    public function test_log()
    {
        $this->assertFalse(Log::channel()->driver()->hasErrorRecords());
        Log::log(LogLevel::ERROR, 'message.');
        $this->assertTrue(Log::channel()->driver()->hasErrorRecords());
    }
}

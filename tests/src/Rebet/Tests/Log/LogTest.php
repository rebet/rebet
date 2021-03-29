<?php
namespace Rebet\Tests\Log;

use Rebet\Log\Driver\Monolog\StderrDriver;

use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Driver\NullDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;

class LogTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
        Config::application([
            Log::class => [
                'unittest' => false,
                'channels' => [
                    'test' => [
                        'driver' => [
                            '@factory' => TestDriver::class,
                            'name'     => 'test',
                            'level'    => LogLevel::WARNING,
                        ],
                    ],
                    'stderr' => [
                        'driver' => [
                            '@factory' => StderrDriver::class,
                            'name'     => 'stderr',
                            'level'    => LogLevel::DEBUG,
                            'format'   => "{datetime} [{channel}.{level_name}] {extra.process_id} {message}{context}{extra}{exception}\n",
                        ],
                    ],
                    'missing_driver' => [
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
        $this->assertStderrContainsAll(
            [
                "2010-10-20 10:20:30.040050 rebet/{$process_id} [WARNING] Unable to create 'nothing' channel logger",
                "Unable to instantiate 'channels.nothing.driver' in Log. Undefined configure 'Rebet\Log\Log.channels.nothing.driver'."
            ],
            function () {
                $this->assertInstanceOf(NullDriver::class, Log::channel('nothing')->driver());
            }
        );
        $this->assertStderrContainsAll(
            [
                "2010-10-20 10:20:30.040050 rebet/{$process_id} [WARNING] Unable to create 'missing_driver' channel logger",
                "Unable to instantiate 'channels.missing_driver.driver' in Log. Undefined configure 'Rebet\Log\Log.channels.missing_driver.driver'."
            ],
            function () {
                $this->assertInstanceOf(NullDriver::class, Log::channel('missing_driver')->driver());
            }
        );
    }

    public function test_stack()
    {
        $process_id = getmypid();
        $this->assertStderrEquals(
            "2010-10-20 10:20:30.040050 [stderr.ERROR] {$process_id} Somthing error happened.\n",
            function () {
                Log::stack('test', 'stderr')->error('Somthing error happened.');
            }
        );
        $this->assertSame(
            "2010-10-20 10:20:30.040050 test/{$process_id} [ERROR] Somthing error happened.\n",
            Log::channel('test')->driver()->formatted()
        );

        $this->assertStderrEquals(
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
        $this->assertStringContainsString('DEBUG', $driver->formatted());

        $driver->clear();
        Log::info('Test');
        $this->assertStringContainsString('INFO', $driver->formatted());

        $driver->clear();
        Log::notice('Test');
        $this->assertStringContainsString('NOTICE', $driver->formatted());

        $driver->clear();
        Log::warning('Test');
        $this->assertStringContainsString('WARNING', $driver->formatted());

        $driver->clear();
        Log::error('Test');
        $this->assertStringContainsString('ERROR', $driver->formatted());

        $driver->clear();
        Log::critical('Test');
        $this->assertStringContainsString('CRITICAL', $driver->formatted());

        $driver->clear();
        Log::alert('Test');
        $this->assertStringContainsString('ALERT', $driver->formatted());

        $driver->clear();
        Log::emergency('Test');
        $this->assertStringContainsString('EMERGENCY', $driver->formatted());
    }

    public function test_memory()
    {
        $driver = Log::channel()->driver();
        $driver->clear();
        Log::memory();
        $this->assertStringContainsString('Memory', $driver->formatted());
    }

    public function test_log()
    {
        $this->assertFalse(Log::channel()->driver()->hasErrorRecords());
        Log::log(LogLevel::ERROR, 'message.');
        $this->assertTrue(Log::channel()->driver()->hasErrorRecords());
    }
}

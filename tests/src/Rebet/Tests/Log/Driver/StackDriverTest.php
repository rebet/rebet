<?php
namespace Rebet\Tests\Log\Driver;

use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Driver\StackDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class StackDriverTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
    }

    public function test___construct()
    {
        $this->assertInstanceOf(StackDriver::class, new StackDriver(['test', 'stderr']));
    }

    public function test_log()
    {
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
                    ]
                ]
            ]
        ]);

        $process_id = getmypid();
        $stack      = new StackDriver(['test', 'stderr']);
        $this->assertSameStderr(
            "2010-10-20 10:20:30.123456 [stderr.ERROR] {$process_id} Somthing error happened.\n",
            function () use ($stack) {
                $stack->error('Somthing error happened.');
            }
        );
        $this->assertSame(
            "2010-10-20 10:20:30.123456 test/{$process_id} [ERROR] Somthing error happened.\n",
            Log::channel('test')->driver()->formatted()
        );

        $this->assertSameStderr(
            "2010-10-20 10:20:30.123456 [stderr.INFO] {$process_id} Somthing infomation.\n",
            function () use ($stack) {
                $stack->info('Somthing infomation.');
            }
        );
        $this->assertFalse(Log::channel('test')->driver()->hasInfoRecords());
    }
}

<?php

declare(strict_types=1);

namespace Rebet\Tests\Log\Driver;

use Psr\Log\LoggerInterface;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Driver\StackDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;

class StackDriverTest extends RebetTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
    }

    public function test___construct(): void
    {
        $this->assertInstanceOf(StackDriver::class, new StackDriver(['test', 'stderr']));
    }

    public function test_log(): void
    {
        Config::application([
            Log::class => [
                'unittest' => false,
                'channels' => [
                    'test'   => [
                        'driver' => [
                            '@factory' => TestDriver::class,
                            'level'    => LogLevel::WARNING,
                        ],
                    ],
                    'stderr' => [
                        'driver' => [
                            '@factory' => StderrDriver::class,
                            'level'    => LogLevel::DEBUG,
                            'format'   => "{datetime} [{channel}.{level_name}] {extra.process_id} {message}{context}{extra}{exception}\n",
                        ],
                    ],
                ],
            ],
        ]);

        $process_id = getmypid();
        $stack      = new StackDriver(['test', 'stderr']);
        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [stderr.ERROR] {$process_id} Somthing error happened.\n",
            function () use ($stack): void {
                $stack->error('Somthing error happened.');
            },
        );
        $this->assertSame(
            "2010-10-20 10:20:30.123456 test/{$process_id} [ERROR] Somthing error happened.\n",
            Log::channel('test')->driver()->formatted(),
        );

        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [stderr.INFO] {$process_id} Somthing infomation.\n",
            function () use ($stack): void {
                $stack->info('Somthing infomation.');
            },
        );
        $this->assertFalse(Log::channel('test')->driver()->hasInfoRecords());
    }

    public function test_log_withSetName(): void
    {
        Config::application([
            Log::class => [
                'unittest' => false,
                'channels' => [
                    'test'   => [
                        'driver' => [
                            '@factory' => TestDriver::class,
                            'level'    => LogLevel::WARNING,
                        ],
                    ],
                    'stderr' => [
                        'driver' => [
                            '@factory' => StderrDriver::class,
                            'level'    => LogLevel::DEBUG,
                            'format'   => "{datetime} [{channel}.{level_name}] {extra.process_id} {message}{context}{extra}{exception}\n",
                        ],
                    ],
                ],
            ],
        ]);

        $process_id = getmypid();
        $stack      = new StackDriver(['test', 'stderr']);
        $this->assertInstanceOf(LoggerInterface::class, $stack->setName('foo'));
        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [foo.ERROR] {$process_id} Somthing error happened.\n",
            function () use ($stack): void {
                $stack->error('Somthing error happened.');
            },
        );
        $this->assertSame(
            "2010-10-20 10:20:30.123456 foo/{$process_id} [ERROR] Somthing error happened.\n",
            Log::channel('test')->driver()->formatted(),
        );

        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [foo.INFO] {$process_id} Somthing infomation.\n",
            function () use ($stack): void {
                $stack->info('Somthing infomation.');
            },
        );
        $this->assertFalse(Log::channel('test')->driver()->hasInfoRecords());
    }

    public function test_log_withName(): void
    {
        Config::application([
            Log::class => [
                'unittest' => false,
                'channels' => [
                    'test'   => [
                        'driver' => [
                            '@factory' => TestDriver::class,
                            'level'    => LogLevel::WARNING,
                        ],
                    ],
                    'stderr' => [
                        'driver' => [
                            '@factory' => StderrDriver::class,
                            'level'    => LogLevel::DEBUG,
                            'format'   => "{datetime} [{channel}.{level_name}] {extra.process_id} {message}{context}{extra}{exception}\n",
                        ],
                    ],
                ],
            ],
        ]);

        $process_id = getmypid();
        $stack      = new StackDriver(['test', 'stderr']);
        $stack_foo  = $stack->withName('foo');
        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [stderr.ERROR] {$process_id} Somthing error happened.\n",
            function () use ($stack): void {
                $stack->error('Somthing error happened.');
            },
        );
        $this->assertSame(
            "2010-10-20 10:20:30.123456 test/{$process_id} [ERROR] Somthing error happened.\n",
            Log::channel('test')->driver()->formatted(),
        );

        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [stderr.INFO] {$process_id} Somthing infomation.\n",
            function () use ($stack): void {
                $stack->info('Somthing infomation.');
            },
        );
        $this->assertFalse(Log::channel('test')->driver()->hasInfoRecords());

        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [foo.ERROR] {$process_id} Somthing error happened.\n",
            function () use ($stack_foo): void {
                $stack_foo->error('Somthing error happened.');
            },
        );
        $this->assertSame(
            "2010-10-20 10:20:30.123456 test/{$process_id} [ERROR] Somthing error happened.\n"
            . "2010-10-20 10:20:30.123456 foo/{$process_id} [ERROR] Somthing error happened.\n",
            Log::channel('test')->driver()->formatted(),
        );

        $this->assertStderrEquals(
            "2010-10-20 10:20:30.123456 [foo.INFO] {$process_id} Somthing infomation.\n",
            function () use ($stack_foo): void {
                $stack_foo->info('Somthing infomation.');
            },
        );
        $this->assertFalse(Log::channel('test')->driver()->hasInfoRecords());

    }
}

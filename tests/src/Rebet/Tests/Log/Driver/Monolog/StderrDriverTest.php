<?php

declare(strict_types=1);

namespace Rebet\Tests\Log\Driver\Monolog;

use Monolog\Handler\StreamHandler;
use Monolog\Processor\ProcessIdProcessor;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class StderrDriverTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $driver = new StderrDriver(LogLevel::DEBUG);
        $this->assertInstanceOf(StderrDriver::class, $driver);
        $this->assertInstanceOf(ProcessIdProcessor::class, $driver->popProcessor());
        $handler = $driver->popHandler();
        $this->assertInstanceOf(StreamHandler::class, $handler);
        if (defined('STDERR')) {
            $this->assertSame(STDERR, $handler->getStream());
        } else {
            $this->assertSame('php://stderr', $handler->getUrl());
        }
    }
}

<?php

declare(strict_types=1);

namespace Rebet\Tests\Console;

use Override;
use Rebet\Application\Console\Command\EnvCommand;
use Rebet\Console\Application;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ApplicationTest extends RebetTestCase
{
    /** @var Application */
    protected $app;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application('unittest', '0.1.0');
        $this->app->add(new EnvCommand());
        $this->app->setAutoExit(false);
    }

    public function test_call(): void
    {
        $buffer = new BufferedOutput();
        $return = $this->app->call('env', [], $buffer);
        $this->assertSame("Current application environment: unittest." . PHP_EOL, $buffer->fetch());
        $this->assertSame($return, 0);
    }

    public function test_execute(): void
    {
        $buffer = new BufferedOutput();
        $return = $this->app->execute('env', $buffer);
        $this->assertSame("Current application environment: unittest." . PHP_EOL, $buffer->fetch());
        $this->assertSame($return, 0);
    }
}

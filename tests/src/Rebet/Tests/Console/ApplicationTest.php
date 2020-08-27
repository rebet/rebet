<?php
namespace Rebet\Tests\Console;

use Rebet\Application\Console\Command\EnvCommand;
use Rebet\Console\Application;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ApplicationTest extends RebetTestCase
{
    /** @var Application */
    protected $app;

    protected function setUp() : void
    {
        parent::setUp();
        $this->app = new Application('unittest', '0.1.0');
        $this->app->add(new EnvCommand());
        $this->app->setAutoExit(false);
    }

    public function test_call()
    {
        $buffer = new BufferedOutput();
        $return = $this->app->call('env', [], $buffer);
        $this->assertSame("Current application environment: unittest.".PHP_EOL, $buffer->fetch());
        $this->assertSame($return, 0);
    }

    public function test_execute()
    {
        $buffer = new BufferedOutput();
        $return = $this->app->execute('env', $buffer);
        $this->assertSame("Current application environment: unittest.".PHP_EOL, $buffer->fetch());
        $this->assertSame($return, 0);
    }
}

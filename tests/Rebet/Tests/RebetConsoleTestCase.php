<?php
namespace Rebet\Tests;

use Rebet\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Rebet Console Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetConsoleTestCase extends RebetTestCase
{
    /**
     * @var array of test commands
     */
    const COMMANDS = [];

    /** @var Application */
    protected $app;

    protected function setUp() : void
    {
        parent::setUp();
        $this->app = new Application();
        foreach (static::COMMANDS as $command) {
            $this->app->add(new $command);
        }
    }

    protected function getTester(string $command) : CommandTester
    {
        return new CommandTester($this->app->find($command));
    }

    protected function doRun(string $command)
    {
        return $this->app->run(new StringInput($command));
    }
}

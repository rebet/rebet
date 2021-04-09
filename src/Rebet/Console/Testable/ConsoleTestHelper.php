<?php
namespace Rebet\Console\Testable;

use Rebet\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Console Test Helper Trait
 *
 * The assertion methods are declared static and can be invoked from any context, for instance,
 * using static::assert*() or $this->assert*() in a class that use TestHelper.
 *
 * It expect this trait to be used in below,
 *  - Class that extended PHPUnit\Framework\TestCase(actual PHPUnit\Framework\Assert) class.
 *  - Class that used Rebet\Tools\Testable\TestHelper trait.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait ConsoleTestHelper
{
    /** @var Application */
    protected $app;

    /**
     * Set up console application for given commands.
     *
     * @param string ...$commands
     * @return void
     */
    protected function setUpConsole(string ...$commands) : void
    {
        $this->app = new Application();
        foreach ($commands as $command) {
            $this->app->add(new $command);
        }
    }

    /**
     * Get command tester for given command.
     *
     * @param string $command
     * @return CommandTester
     */
    protected function getCommandTester(string $command) : CommandTester
    {
        return new CommandTester($this->app->find($command));
    }

    /**
     * Execute command.
     *
     * @param string $command
     * @return void
     */
    protected function execute(string $command)
    {
        return $this->app->run(new StringInput($command));
    }

    // ========================================================================
    // Dependent PHPUnit\Framework\Assert assertions
    // ========================================================================


    // ========================================================================
    // Dependent Rebet\Tools\Testable\TestHelper methods and assertions
    // ========================================================================


    // ========================================================================
    // Extended assertions
    // ========================================================================
}

<?php
namespace Rebet\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Application Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Application extends SymfonyApplication
{
    /**
     * Run a command by name.
     *
     * @param string $command
     * @param array $parameters (default: [])
     * @param OutputInterface|null $output (default: null)
     * @return int
     */
    public function call(string $command, array $parameters = [], ?OutputInterface $output = null) : int
    {
        return $this->run(new ArrayInput(array_merge($parameters, ['command' => $command])), $output);
    }

    /**
     * Execute a given command line.
     *
     * @param string $command_line
     * @param OutputInterface|null $output
     * @return integer
     */
    public function execute(string $command_line, ?OutputInterface $output = null) : int
    {
        return $this->run(new StringInput($command_line), $output);
    }
}

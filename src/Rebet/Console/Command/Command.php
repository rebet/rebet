<?php
namespace Rebet\Console\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Command extends SymfonyCommand
{
    /**
     * The name of command.
     * Must be overloaded in subclass.
     * @var string
     */
    const NAME = null;

    /**
     * The description of command.
     * Must be overloaded in subclass.
     * @var string
     */
    const DESCRIPTION = null;

    /**
     * The arguments of command.
     * Must be overloaded in subclass if necessary.
     * @var array
     */
    const ARGUMENTS = [];

    /**
     * The options of command.
     * Must be overloaded in subclass if necessary.
     * @var array
     */
    const OPTIONS = [];

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(static::NAME);
        $this->setDescription(static::DESCRIPTION);
        foreach (static::ARGUMENTS as $argment) {
            $this->addArgument(...$argment);
        }
        foreach (static::OPTIONS as $option) {
            $this->addOption(...$option);
        }
    }
}

<?php
namespace Rebet\Application\Console\Command;

use Rebet\Application\App;
use Rebet\Console\Command\Command;

/**
 * Env Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EnvCommand extends Command
{
    const NAME        = 'env';
    const DESCRIPTION = 'Display the current framework environment';

    /**
     * {@inheritDoc}
     */
    protected function handle()
    {
        $this->writeln('<info>Current application environment:</info> <comment>'.App::env().'.</comment>');
    }
}

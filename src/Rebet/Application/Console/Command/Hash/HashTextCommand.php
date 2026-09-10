<?php
declare(strict_types=1);

namespace Rebet\Application\Console\Command\Hash;

use Rebet\Console\Command\Command;
use Rebet\Tools\Utility\Securities;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Hash Text Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class HashTextCommand extends Command
{
    const NAME        = 'hash:text';
    const DESCRIPTION = 'Hash the given text';
    const ARGUMENTS   = [
        ['text', InputArgument::REQUIRED, 'Text that you want to hash'],
    ];
    const OPTIONS = [
        ['salt'      , 's', InputOption::VALUE_OPTIONAL, 'Salt'],
        ['pepper'    , 'p', InputOption::VALUE_OPTIONAL, 'Pepper'],
        ['algorithm' , 'a', InputOption::VALUE_OPTIONAL, 'Hash algorithm'],
        ['stretching', 't', InputOption::VALUE_OPTIONAL, 'Stretching count'],
    ];

    /**
     * {@inheritDoc}
     */
    protected function handle()
    {
        $stretching = $this->option('stretching');

        $this->writeln('<info>Hashed text:</info> '. Securities::hash(
            $this->argument('text'),
            $this->option('salt'),
            $this->option('pepper'),
            $this->option('algorithm'),
            $stretching === null ? null : (int) $stretching
        ));
    }
}

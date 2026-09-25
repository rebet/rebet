<?php
declare(strict_types=1);

namespace Rebet\Application\Console\Command\Hash;

use Rebet\Auth\Password;
use Rebet\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Hash Password Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class HashPasswordCommand extends Command
{
    const NAME        = 'hash:password';
    const DESCRIPTION = 'Hash the given password';
    const ARGUMENTS   = [
        ['password', InputArgument::REQUIRED, 'Password that you want to hash'],
    ];
    const OPTIONS = [
        ['algorithm', 'a', InputOption::VALUE_OPTIONAL, 'Hash algorithm'],
        ['option'   , 'o', InputOption::VALUE_OPTIONAL, 'Algorithm option (JSON)'],
    ];

    /**
     * {@inheritDoc}
     */
    protected function handle()
    {
        $option = null;
        if ($this->option('option')) {
            $option = json_decode($this->option('option'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Can not parse --option JSON : ".json_last_error_msg());
                return 1;
            }
        }

        $this->writeln('<info>Hashed password:</info> '. Password::hash($this->argument('password'), $this->option('algorithm'), $option));
    }
}

<?php
namespace Rebet\Application\Console\Command;

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
    const OPTIONS     = [
        ['algorithm', 'a', InputOption::VALUE_OPTIONAL, 'Hash algorithm'],
        ['option'   , 'o', InputOption::VALUE_OPTIONAL, 'Algorithm option (JSON)'],
    ];

    /**
     * {@inheritDoc}
     */
    protected function handle()
    {
        $option = $this->option('option') ? json_decode($this->option('option'), true) : null ;
        if(($error = json_last_error_msg()) !== 'No error') {
            $this->error("Can not parse --option JSON : {$error}");
            return 1;
        }

        $this->writeln('<info>Hashed password:</info> '. Password::hash($this->argument('password'), $this->option('algorithm'), $option));
    }
}

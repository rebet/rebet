<?php

declare(strict_types=1);

namespace Rebet\Application\Console\Command\Hash;

use Rebet\Console\Command\Command;
use Rebet\Tools\Utility\Securities;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Hash Hmac Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class HashHmacCommand extends Command
{
    public const NAME        = 'hash:hmac';
    public const DESCRIPTION = 'Generate an HMAC for the given text';
    public const ARGUMENTS   = [
        ['text', InputArgument::REQUIRED, 'Text that you want to generate an HMAC for'],
    ];
    public const OPTIONS = [
        ['secret-key', 'sk', InputOption::VALUE_OPTIONAL, 'Secret key'],
        ['algorithm', 'a', InputOption::VALUE_OPTIONAL, 'HMAC algorithm'],
    ];

    /**
     * {@inheritDoc}
     */
    protected function handle()
    {
        $this->writeln('<info>HMAC:</info> ' . Securities::hmac(
            $this->argument('text'),
            $this->option('secret-key'),
            $this->option('algorithm'),
        ));
    }
}

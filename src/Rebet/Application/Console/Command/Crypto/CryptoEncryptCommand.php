<?php
declare(strict_types=1);

namespace Rebet\Application\Console\Command\Crypto;

use Rebet\Console\Command\Command;
use Rebet\Tools\Utility\Nets;
use Rebet\Tools\Utility\Securities;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Crypto Encrypt Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class CryptoEncryptCommand extends Command
{
    const NAME        = 'crypto:encrypt';
    const DESCRIPTION = 'Encrypt the given text';
    const ARGUMENTS   = [
        ['plain', InputArgument::REQUIRED, 'Text that you want to encrypt'],
    ];
    const OPTIONS = [
        ['secret-key'     , 'sk', InputOption::VALUE_OPTIONAL, 'Secret key'],
        ['cipher'         , 'c', InputOption::VALUE_OPTIONAL, 'Cipher'],
        ['hmac-secret-key', 'hsk', InputOption::VALUE_OPTIONAL, 'HMAC secret key'],
        ['hmac-algorithm' , 'ha', InputOption::VALUE_OPTIONAL, 'HMAC algorithm'],
    ];

    /**
     * {@inheritDoc}
     */
    protected function handle()
    {
        $encrypted = Securities::encrypt(
            $this->argument('plain'),
            $this->option('secret-key'),
            $this->option('cipher'),
            $this->option('hmac-secret-key'),
            $this->option('hmac-algorithm')
        );

        $this->writeln('<info>Encrypted:</info> '. Nets::encodeBase64Url($encrypted));
    }
}

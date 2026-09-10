<?php
declare(strict_types=1);

namespace Rebet\Application\Console\Command\Crypto;

use Rebet\Console\Command\Command;
use Rebet\Tools\Utility\Nets;
use Rebet\Tools\Utility\Securities;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Crypto Decrypt Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class CryptoDecryptCommand extends Command
{
    const NAME        = 'crypto:decrypt';
    const DESCRIPTION = 'Decrypt the given encrypted text';
    const ARGUMENTS   = [
        ['encrypted', InputArgument::REQUIRED, 'Encrypted text (that Securities::encrypt()/CryptoEncryptCommand outputs) that you want to decrypt'],
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
        $decrypted = Securities::decrypt(
            Nets::decodeBase64Url($this->argument('encrypted')),
            $this->option('secret-key'),
            $this->option('cipher'),
            $this->option('hmac-secret-key'),
            $this->option('hmac-algorithm')
        );

        if ($decrypted === null) {
            $this->error('Decryption failed. The given text is invalid or was encrypted with different keys.');
            return 1;
        }

        $this->writeln('<info>Decrypted:</info> '. $decrypted);
    }
}

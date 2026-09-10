<?php
declare(strict_types=1);

namespace Rebet\Application\Console;

use Rebet\Application\Console\Command\Crypto\CryptoDecryptCommand;
use Rebet\Application\Console\Command\Crypto\CryptoEncryptCommand;
use Rebet\Application\Console\Command\EnvCommand;
use Rebet\Application\Console\Command\Hash\HashHmacCommand;
use Rebet\Application\Console\Command\Hash\HashPasswordCommand;
use Rebet\Application\Console\Command\Hash\HashTextCommand;
use Rebet\Application\Console\Command\InitCommand;
use Rebet\Console\Application;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Reflection\Reflector;

/**
 * Assistant Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Assistant extends Application
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/application.lp.php
     */
    public static function defaultConfig()
    {
        return [
            'commands' => [
                InitCommand::class,
                EnvCommand::class,
                HashPasswordCommand::class,
                HashTextCommand::class,
                HashHmacCommand::class,
                CryptoEncryptCommand::class,
                CryptoDecryptCommand::class,
            ],
        ];
    }

    /**
     * Create Rebet assistant console application.
     */
    public function __construct()
    {
        parent::__construct();
        foreach (static::config('commands') as $command) {
            $this->addCommand(Reflector::instantiate($command));
        }
    }
}

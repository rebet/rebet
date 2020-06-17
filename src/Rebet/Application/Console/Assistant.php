<?php
namespace Rebet\Application\Console;

use Rebet\Application\Console\Command\EnvCommand;
use Rebet\Application\Console\Command\InitCommand;
use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\Console\Application;

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

    public static function defaultConfig()
    {
        return [
            'commands' => [
                InitCommand::class,
                EnvCommand::class,
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
            $this->add(Reflector::instantiate($command));
        }
    }
}

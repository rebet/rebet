<?php
namespace Rebet\Application\Bootstrap;

use Rebet\Application\Kernel;
use Rebet\Env\Dotenv;

/**
 * Load Environment Variables Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LoadEnvironmentVariables implements Bootstrapper
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        Dotenv::load($kernel->structure()->env());
    }
}

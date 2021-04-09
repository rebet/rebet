<?php
namespace Rebet\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Kernel;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Resource\EnvResource;

/**
 * Load Application Configuration Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LoadApplicationConfiguration implements Bootstrapper
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        Config::application(EnvResource::load(App::env(), $kernel->structure()->configs()));
    }
}

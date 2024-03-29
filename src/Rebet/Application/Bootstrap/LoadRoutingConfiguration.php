<?php
namespace Rebet\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Kernel;
use Rebet\Tools\Resource\EnvResource;

/**
 * Load Routing Configuration Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LoadRoutingConfiguration implements Bootstrapper
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        EnvResource::load(App::env(), $kernel->structure()->routes());
    }
}

<?php

declare(strict_types=1);

namespace Rebet\Application\Bootstrap;

use Override;
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
    #[Override]
    public function bootstrap(Kernel $kernel): void
    {
        Config::application(EnvResource::load(App::env(), $kernel->structure()->config()));
    }
}

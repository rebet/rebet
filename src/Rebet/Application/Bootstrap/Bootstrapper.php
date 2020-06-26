<?php


namespace Rebet\Application\Bootstrap;

use Rebet\Application\Kernel;

/**
 * Bootstrapper Interrface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Bootstrapper
{
    /**
     * Bootstrap proccess execute for the application.
     *
     * @param Kernel $kernel of this application
     * @return void
     */
    public function bootstrap(Kernel $kernel);
}

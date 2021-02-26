<?php
namespace Rebet\Application\Bootstrap;

use Rebet\Application\Kernel;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Tools\Config\Config;

/**
 * Properties Masking Configuration Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PropertiesMaskingConfiguration implements Bootstrapper
{
    /**
     * @var string[] masking property names
     */
    protected array $masks = [];

    /**
     * Create properties masking bootstrapper.
     *
     * @param array $masks property names
     */
    public function __construct(array $masks)
    {
        $this->masks = $masks;
    }

    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        Config::framework([
            //---------------------------------------------
            // Logging Configure
            //---------------------------------------------
            TextFormatter::class => [
                'masks' => $this->masks,
            ],
        ]);
    }
}

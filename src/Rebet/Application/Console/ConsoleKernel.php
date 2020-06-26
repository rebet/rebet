<?php
namespace Rebet\Application\Console;

use Rebet\Application\Bootstrap\LoadConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Kernel as ApplicationKernel;
use Rebet\Application\Structure;

/**
 * Console Kernel Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class ConsoleKernel extends ApplicationKernel
{
    /**
     * {@inheritDoc}
     *
     * @param Structure $structure
     * @param string $channel (default: 'console')
     */
    public function __construct(Structure $structure, string $channel = 'console')
    {
        parent::__construct($structure, $channel);
    }

    /**
     * {@inheritDoc}
     */
    protected function bootstrappers() : array
    {
        return [
            LoadEnvironmentVariables::class,
            LoadConfiguration::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface $input (default: null)
     * @param OutputInterface $output (default: null)
     * @return int
     */
    public function handle($input = null, $output = null)
    {
        return $this->assistant()->run($input, $output);
    }

    /**
     * Run a command by name.
     *
     * @param string $action
     * @param array $parameters (default: [])
     * @param OutputInterface $output (default: null)
     * @return int
     */
    public function call(string $action, array $parameters = [], $output = null)
    {
        return $this->assistant()->call($action, $parameters, $output);
    }

    /**
     * Terminate the application.
     *
     * @param InputInterface $input
     * @param int $result
     * @return void
     */
    public function terminate($input, $result) : void
    {
        // Currently nothing to do.
    }
}

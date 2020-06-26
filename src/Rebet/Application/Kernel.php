<?php
namespace Rebet\Application;

use Rebet\Application\Console\Assistant;
use Rebet\Common\Reflector;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Kernel Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Kernel
{
    /**
     * Application structure definition
     *
     * @var Structure
     */
    protected $structure;

    /**
     * The channel name
     *
     * @var string
     */
    protected $channel;

    /**
     * Rebet assistant console application.
     *
     * @var Assistant
     */
    protected $assistant;

    /**
     * Create the application kernel
     *
     * @param Structure $structure
     * @param string $channel
     */
    public function __construct(Structure $structure, string $channel)
    {
        $this->structure = $structure;
        $this->channel   = $channel;
    }

    /**
     * Get application structure definition
     *
     * @return Structure
     */
    public function structure() : Structure
    {
        return $this->structure;
    }

    /**
     * Get application channel name.
     *
     * @return string
     */
    public function channel() : string
    {
        return $this->channel;
    }

    /**
     * Get rebet assistant console application.
     *
     * @return Assistant
     */
    public function assistant() : Assistant
    {
        return $this->assistant;
    }

    /**
     * Get core bootstrappers for this kernel.
     *
     * @return Bootstrapper[]
     */
    abstract protected function bootstrappers() : array;

    /**
     * Execute bootstrap processes.
     *
     * @return void
     */
    public function bootstrap() : void
    {
        foreach ($this->bootstrappers() as $bootstrapper) {
            Reflector::instantiate($bootstrapper)->bootstrap($this);
        }
        $this->assistant = new Assistant();
    }

    /**
     * Handle the given input data.
     *
     * @param mixed|Request|InputInterface $input
     * @param mixed|null|OutputInterface $output (default: null)
     * @return mixed|Response|int
     */
    abstract public function handle($input, $output = null);

    /**
     * Run an action/command by name.
     *
     * @param string $action
     * @param array $parameters (default: [])
     * @param mixed|null|OutputInterface $output (default: null)
     * @return mixed|Response|int
     */
    abstract public function call(string $action, array $parameters = [], $output = null);

    /**
     * Terminate the application.
     *
     * @param mixed|Request|InputInterface $input
     * @param mixed|Response|int $result
     * @return void
     */
    abstract public function terminate($input, $result) : void;
}

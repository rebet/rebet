<?php
namespace Rebet\Application;

use Rebet\Application\Console\Assistant;
use Rebet\Application\Error\ExceptionHandler;
use Rebet\Tools\Reflection\Reflector;
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
     * @param mixed|null|Request|InputInterface $input (default: null)
     * @param mixed|null|OutputInterface $output (default: null)
     * @return mixed|Response|int
     */
    abstract public function handle($input = null, $output = null);

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
     * @return void
     */
    abstract public function terminate() : void;

    /**
     * Get exception handler.
     *
     * @return ExceptionHandler
     */
    abstract public function exceptionHandler() : ExceptionHandler;

    /**
     * Report an uncaught exception then display fallback pages(console messages).
     *
     * @param \Throwable $e
     * @return int error code for exit()
     */
    abstract public function fallback(\Throwable $e) : int;

    /**
     * Report an uncaught exception.
     * Just only report, this function do not response and display result.
     *
     * @param \Throwable $e
     * @return void
     */
    abstract public function report(\Throwable $e) : void;
}

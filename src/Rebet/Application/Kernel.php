<?php
namespace Rebet\Application;

use Rebet\Application\Bootstrap\Bootstrapper;
use Rebet\Http\Response;
use Rebet\Tools\Reflection\Reflector;

/**
 * Kernel Class
 *
 * @template I
 * @template R
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
    protected Structure $structure;

    /**
     * The channel name
     *
     * @var string
     */
    protected string $channel;

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
     * Get core bootstrappers for this kernel.
     *
     * @return array<int, Bootstrapper|class-string<Bootstrapper>|array<int|string, mixed>> class name of Bootstrapper, or [class name, ...args] for Reflector::instantiate()
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
    }

    /**
     * Handle the given input data.
     *
     * @param I|null $input (default: null)
     * @return R
     */
    abstract public function handle($input = null);

    /**
     * Run an action/command by name.
     *
     * @param string $action
     * @param array<string, mixed> $parameters (default: [])
     * @return R
     */
    abstract public function call(string $action, array $parameters = []);

    /**
     * Terminate the application.
     *
     * @return void
     */
    abstract public function terminate() : void;

    /**
     * Get exception handler.
     *
     * @return ExceptionHandler<I, R>
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

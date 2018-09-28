<?php
namespace Rebet\Pipeline;

use Rebet\Common\Reflector;
use Rebet\Common\Utils;

/**
 * Pipeline Class
 *
 * This class based on Illuminate\Pipeline\Pipeline of laravel/framework ver 5.7.
 * But this class dose not contain DI Container of laravel.
 *
 * Function diffs between Laravel and Rebet are like below;
 *  - remove illuminate modules dependency. (dependency injection container and Responsable)
 *  - unsupported: full pipe string to get name and parameters. (additional handle parameters)
 *  + supported: multi instantiate way based on Rebet\Common\Reflector::instantiate() like array for constract with parameters. (exclude callable type)
 *  + supported: invoke any method of instantiated pipes.
 *  # changed: then() to be pipeline builder and send() to be pipeline runner.
 *
 * @see https://github.com/laravel/framework/blob/5.7/src/Illuminate/Pipeline/Pipeline.php
 * @see https://github.com/laravel/framework/blob/5.7/LICENSE.md
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The array of latest instantiated pipes.
     *
     * @var array
     */
    protected $real_pipes = [];
    
    /**
     * The entry point closure of pipeline.
     *
     * @var \Closure
     */
    protected $pipeline = null;

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * The final destination callback.
     *
     * @var callable
     */
    protected $destination = null;

    /**
     * Run the pipeline.
     *
     * @param  mixed  $passable
     * @return mixed
     * @throws LogicException
     */
    public function send($passable)
    {
        if ($this->pipeline === null) {
            throw new \LogicException('Pipeline not build yet. You shold buld a pipeline using then() first.');
        }
        return ($this->pipeline)($passable);
    }
    
    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes) : self
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method) : self
    {
        $this->method = $method;
        return $this;
    }
    
    /**
     * Build the pipeline with a final destination callback.
     *
     * @param  callable $destination
     * @return $this
     */
    public function then(callable $destination)
    {
        $this->destination = $destination;
        $this->real_pipes  = [];
        $this->pipeline    = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );
        return $this;
    }
    
    /**
     * Get final destination callback.
     *
     * @return callable|null
     */
    public function getDestination() : ?callable
    {
        return $this->destination;
    }

    /**
     * Invoke any method of the instantiated pipes if exsits. (exclude final destination callback)
     *
     * @param  string  $method
     * @param  mixed  $args
     * @return $this
     */
    public function invoke(string $method, ...$args) : self
    {
        foreach ($this->real_pipes as $pipe) {
            if (method_exists($pipe, $method)) {
                $pipe->{$method}(...$args);
            }
        }
        return $this;
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param  callable $destination
     * @return \Closure
     */
    protected function prepareDestination(callable $destination) : \Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry() : \Closure
    {
        return function ($stack, $pipe) {
            $pipe = \is_callable($pipe) ? $pipe : Reflector::instantiate($pipe) ;
            $this->real_pipes[] = $pipe;
            
            return function ($passable) use ($stack, $pipe) {
                return method_exists($pipe, $this->method) ? $pipe->{$this->method}($passable, $stack) : $pipe($passable, $stack);
            };
        };
    }
}

<?php
namespace Rebet\Pipeline;

/**
 * Pipeline Class
 * 
 * This class based on Illuminate/Pipeline of laravel/framework 5.7.
 * But this class dose not contain DI Container of laravel.
 * 
 * Function diffs between Laravel and Rebet are like below;
 *  - remove illuminate modules dependency. (dependency injection container and Responsable)
 *  - unsuported full pipe string to get name and parameters. (unsuported additional handle parameters)
 *  + suport array of pipe class and constract parameters.
 * 
 * @see https://github.com/laravel/framework/blob/5.7/src/Illuminate/Pipeline/Pipeline.php
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Pipeline {

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;
    
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The array of instantiated pipes.
     *
     * @var array
     */
    protected $real_pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable) : self
    {
        $this->passable = $passable;
        return $this;
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
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(\Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination)
        );
        return $pipeline($this->passable);
    }    
    
    /**
     * Run any method of the instantiated pipes.
     *
     * @param  string  $method
     * @param  mixed  $args
     * @return $this
     */
    public function run(string $method, ...$args) : self
    {
        foreach ($this->real_pipes as $pipe) {
            if(method_exists($pipe, $method)) {
                $pipe->{$method}(...$args);
            }
        }
        return $this;
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(\Closure $destination) : \Closure
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
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    // If the pipe is a string we will just instantiate the class.
                    $pipe = new $pipe();
                } elseif (is_array($pipe)) {
                    // If the pipe is a array we will instantiate the class with constracter parameters.
                    $pipe_class = array_shift($pipe);
                    $pipe       = new $pipe_class(...$pipe);
                }

                $this->real_pipes[] = $pipe;
                return method_exists($pipe, $this->method) ? $pipe->{$this->method}($passable, $stack) : $pipe($passable, $stack);
            };
        };
    }
}

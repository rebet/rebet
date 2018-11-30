<?php
namespace Rebet\Http;

use Rebet\Common\Arrayable;
use Rebet\Common\Reflector;

/**
 * Input Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Input implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Arrayable;

    /**
     * Request (source of input data).
     *
     * @var Request
     */
    protected $request = null;
    
    /**
     * Input data.
     *
     * @var array
     */
    protected $input = null;

    /**
     * Create Input
     *
     * @param string $request
     */
    public function __construct(Request &$request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    protected function &container() : array
    {
        return $this->input ?? $this->input = $this->request->input() ;
    }

    /**
     * Property accessor.
     *
     * @param string $key
     * @return void
     */
    public function __get($key)
    {
        return $this->container()[$key] ?? null ;
    }

    /**
     * Get the value of given key using dot notation.
     *
     * @param string $key of dot notation
     * @param mixed $default (default: null)
     * @return void
     */
    public function get(string $key, $default = null)
    {
        return Reflector::get($this->container(), $key, $default);
    }
}

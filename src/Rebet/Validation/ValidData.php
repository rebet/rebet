<?php
namespace Rebet\Validation;

use Rebet\Common\Arrayable;
use Rebet\Common\Arrays;
use Rebet\Common\Describable;
use Rebet\Common\Reflector;

/**
 * Valid Data Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ValidData implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Arrayable, Describable;

    /**
     * Valid data.
     *
     * @var array
     */
    protected $data = null;

    /**
     * Create Valid Data
     *
     * @param mixed $data
     */
    public function __construct($data = [])
    {
        $this->data = Arrays::toArray($data);
    }

    /**
     * {@inheritDoc}
     */
    protected function &container() : array
    {
        return $this->data;
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

<?php
namespace Rebet\Tools\Support;

use Rebet\Tools\Json;

/**
 * Arrayable Trait
 *
 * This trait cover \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable interfaces.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Arrayable
{
    /**
     * Get array container for arrayable accesss.
     *
     * @return array
     */
    abstract protected function &container() : array ;

    /**
     * Get the all items.
     *
     * @return array
     */
    public function all() : array
    {
        return $this->container();
    }

    /**
     * It checks that items not exists.
     *
     * @return boolean
     */
    public function empty() : bool
    {
        return empty($this->container());
    }

    /**
     * It checks the given key is exists or not.
     *
     * @param int|string $key
     * @return boolean
     */
    public function has($key) : bool
    {
        return array_key_exists($key, $this->container());
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->container());
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->container());
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $container = &$this->container();
        if ($offset === null) {
            $container[] = $value;
        } else {
            $container[$offset] = $value;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->container()[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->container()[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->container()[$offset] ?? null ;
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray() : array
    {
        return array_map(function ($value) {
            return method_exists($value, 'toArray') ? $value->toArray() : $value;
        }, $this->container());
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return Json::serialize($this->container());
    }
}

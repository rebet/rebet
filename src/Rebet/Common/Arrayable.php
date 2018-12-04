<?php
namespace Rebet\Common;

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
     * Get the sub array contains given keys.
     *
     * @param mixed ...$keys
     * @return array
     */
    public function only(...$keys) : array
    {
        return Arrays::only($this->container(), $keys);
    }

    /**
     * Get the sub array except given keys.
     *
     * @param mixed ...$keys
     * @return array
     */
    public function except(...$keys) : array
    {
        return Arrays::except($this->container(), $keys);
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
        $container = $this->container();
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
     * Get the collection of items as a collection.
     *
     * @return array
     */
    public function toCollection() : Collection
    {
        return new Collection(array_map(function ($value) {
            return method_exists($value, 'toCollection') ? $value->toCollection() : $value;
        }, $this->container()));
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
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            } elseif (method_exists($value, 'toJson')) {
                return json_decode($value->toJson(), true);
            } elseif (method_exists($value, 'toArray')) {
                return $value->toArray();
            }
            return $value;
        }, $this->container());
    }
}

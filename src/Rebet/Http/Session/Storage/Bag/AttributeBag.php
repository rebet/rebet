<?php
namespace Rebet\Http\Session\Storage\Bag;

use Rebet\Tools\Reflection\Reflector;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

/**
 * Attribute Bag Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @implements \IteratorAggregate<string, mixed>
 */
class AttributeBag implements SessionBagInterface, \IteratorAggregate, \Countable
{
    /**
     * Bag name.
     *
     * @var string
     */
    private $name;

    /**
     * Strage key.
     *
     * @var string
     */
    private $storage_key;

    /**
     * Attribute data.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [];

    /**
     * Create a session attribute bag.
     *
     * @param string $name
     * @param string|null $storage_key (default: null for "_rebet_{$name}")
     */
    public function __construct(string $name, string|null $storage_key = null)
    {
        $this->name        = $name;
        $this->storage_key = $storage_key ?? "_rebet_{$name}";
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $attributes
     */
    public function initialize(array &$attributes) : void
    {
        $this->attributes = &$attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey() : string
    {
        return $this->storage_key;
    }

    /**
     * {@inheritdoc}
     */
    public function clear() : mixed
    {
        $value            = $this->attributes;
        $this->attributes = [];
        return $value;
    }

    /**
     * It checks exists the key/property of given name.
     *
     * @param string $name You can use dot notation.
     * @return boolean
     */
    public function has(string $name) : bool
    {
        return Reflector::has($this->attributes, $name);
    }

    /**
     * Get the value of given name.
     *
     * @param string $name You can use dot notation.
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return Reflector::get($this->attributes, $name, $default);
    }

    /**
     * Set the value to given name.
     *
     * @param string $name You can use dot notation.
     * @param mixed $value
     * @return void
     */
    public function set(string $name, $value) : void
    {
        Reflector::set($this->attributes, $name, $value);
    }

    /**
     * Get the all attibutes.
     *
     * @return array<string, mixed>
     */
    public function all() : array
    {
        return $this->attributes;
    }

    /**
     * Remove the key/property of given name.
     *
     * @param string $name You can use dot notation.
     * @return mixed removed value
     */
    public function remove($name)
    {
        return Reflector::remove($this->attributes, $name);
    }

    /**
     * Returns an iterator for attributes.
     *
     * @return \ArrayIterator<string, mixed>
     */
    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * Returns the number of attributes.
     *
     * @return int
     */
    public function count() : int
    {
        return \count($this->attributes);
    }
}

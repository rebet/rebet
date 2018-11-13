<?php
namespace Rebet\Http\Session\Storage\Bag;

use Rebet\Common\Reflector;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

/**
 * Flash Bag Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FlashBag implements SessionBagInterface
{
    /**
     * Bag name.
     *
     * @var string
     */
    private $name = null;

    /**
     * Strage key.
     *
     * @var string
     */
    private $storage_key = null;

    /**
     * Attribute data.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a session attribute bag.
     *
     * @param string $name
     * @param string|null $storage_key (default: null for "_rebet_{$name}")
     */
    public function __construct(string $name, ?string $storage_key = null)
    {
        $this->name        = $name;
        $this->storage_key = $storage_key ?? "_rebet_{$name}";
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array &$attributes)
    {
        $this->attributes = &$attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey()
    {
        return $this->storageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
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
     * Peek the value of given name.
     *
     * @param string $name You can use dot notation.
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function peek(string $name, $default = null)
    {
        return Reflector::get($this->attributes, $name, $default);
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
        return $this->remove($name) ?? $default ;
    }

    /**
     * Set the value to given name.
     *
     * @param string $name You can use dot notation.
     * @param mixed $value
     * @param mixed $default (default: null)
     * @return void
     */
    public function set(string $name, $value) : void
    {
        Reflector::set($this->attributes, $name, $value);
    }

    /**
     * Get the all attibutes.
     *
     * @return array
     */
    public function all() : array
    {
        return $this->clear();
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
}

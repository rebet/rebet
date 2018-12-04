<?php
namespace Rebet\Common;

/**
 * Null Contagion Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class NullContagion implements \ArrayAccess
{
    /**
     * Null Contagion what the origin is null
     *
     * @var self
     */
    private static $null = null;

    /**
     * Original instance
     */
    protected $orign = null;

    /**
     * Create a Null Contagion instance
     */
    protected function __construct($orign)
    {
        $this->orign = $orign;
        if (static::$null === null) {
            static::$null = new static(null);
        }
    }

    /**
     * Create a Null Contagion instance
     *
     * @param mixed $orign
     * @return self
     */
    public static function infect($orign)
    {
        return new static($orign);
    }

    /**
     * Get the origin result
     *
     * @return mixed
     */
    public function recover()
    {
        return $this->orign;
    }

    /**
     * Property accessor.
     *
     * @param string $key
     * @return void
     */
    public function __get($key)
    {
        if ($this->orign === null) {
            return static::$null;
        }
        return new static(Reflector::get($this->orign, $key)) ;
    }

    /**
     * Method accessor.
     *
     * @param string $name
     * @param array $args
     * @return void
     */
    public function __call($name, $args)
    {
        if ($this->orign === null) {
            return static::$null;
        }
        return Reflector::invoke($this->origin, $name, $args);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        // Nothing to do.
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        // Always returns true
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        // Nothing to do.
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }
}

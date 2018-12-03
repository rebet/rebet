<?php
namespace Rebet\Validation;

use Rebet\Common\Arrayable;
use Rebet\Common\Arrays;
use Rebet\Common\Collection;
use Rebet\Common\Convertible;
use Rebet\Common\Describable;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;

/**
 * Valid Data Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ValidData implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, Convertible
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

    /**
     * Convert the type from other to self.
     * If conversion is not possible then return null.
     *
     * @param mixed $value
     * @return self
     */
    public static function valueOf($value) : ?self
    {
        if (is_null($value)) {
            return null;
        }
        if (is_string($value)) {
            $value = Strings::contains($value, ',') ? explode(',', $value) : $value ;
        }
        return new static($value);
    }

    /**
     * Convert the type from self to other.
     * If conversion is not possible then return null.
     *
     * @param string $type
     * @return mixed
     */
    public function convertTo(string $type)
    {
        if (Reflector::typeOf($this, $type)) {
            return $this;
        }
        switch ($type) {
            case Collection::class:
                return $this->toCollection();
            case 'array':
                return $this->toArray();
        }

        return null;
    }
}

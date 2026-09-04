<?php
declare(strict_types=1);

namespace Rebet\Attribute;

/**
 * Class attributes accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AttributedClass
{
    /**
     * Reflection class of attribute target
     *
     * @var \ReflectionClass<object>
     */
    protected $class;

    /**
     * Create class attributes accesser.
     *
     * @param string|object|\ReflectionClass $class
     * @return AttributedClass
     */
    public static function of($class) : AttributedClass
    {
        return new AttributedClass($class);
    }

    /**
     * Create class attributes accesser.
     *
     * @param string|object|\ReflectionClass $class
     */
    public function __construct($class)
    {
        $this->class = $class instanceof \ReflectionClass ? $class : new \ReflectionClass($class) ;
    }

    /**
     * Get class attributes
     *
     * @return array<object> [Attribute, ...]
     */
    public function attributes() : array
    {
        return array_map(fn (\ReflectionAttribute $a) => $a->newInstance(), $this->class->getAttributes());
    }

    /**
     * Get class attribute
     *
     * @param string $attribute
     * @return mixed Attribute
     */
    public function attribute(string $attribute)
    {
        $attributes = $this->class->getAttributes($attribute);
        return empty($attributes) ? null : $attributes[0]->newInstance() ;
    }

    /**
     * Get method attribute
     *
     * @param string $method
     * @return AttributedMethod|null
     */
    public function method(string $method) : AttributedMethod|null
    {
        return $this->class->hasMethod($method) ? new AttributedMethod($this->class->getMethod($method), $this) : null ;
    }

    /**
     * Get property attribute
     *
     * @param string $property
     * @return AttributedProperty|null
     */
    public function property(string $property) : AttributedProperty|null
    {
        return $this->class->hasProperty($property) ? new AttributedProperty($this->class->getProperty($property), $this) : null ;
    }

    /**
     * Get property attributes
     *
     * @return AttributedProperty[]
     */
    public function properties() : array
    {
        return array_map(function ($v) { return new AttributedProperty($v, $this); }, $this->class->getProperties());
    }

    /**
     * Get the reflector of target class
     *
     * @return \ReflectionClass<object>
     */
    public function reflector() : \ReflectionClass
    {
        return $this->class;
    }
}

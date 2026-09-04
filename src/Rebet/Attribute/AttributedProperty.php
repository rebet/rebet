<?php
declare(strict_types=1);

namespace Rebet\Attribute;

/**
 * Property attributes accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AttributedProperty
{
    /**
     * Reflection class of attribute target
     *
     * @var \ReflectionProperty
     */
    protected $property;

    /**
     * Attributed declaring class of the property.
     *
     * @var AttributedClass
     */
    protected $attributed_class;

    /**
     * Create property attributes accesser.
     *
     * @param string|\ReflectionProperty $property
     * @param string|object|\ReflectionClass|null $class
     * @return AttributedProperty
     */
    public static function of($property, $class = null) : AttributedProperty
    {
        if (is_string($property)) {
            $property = new \ReflectionProperty($class, $property);
        }
        return new AttributedProperty($property);
    }

    /**
     * Create a property attribute accessor.
     *
     * @param \ReflectionProperty $property
     * @param AttributedClass|null $attributed_class
     */
    public function __construct(\ReflectionProperty $property, AttributedClass|null $attributed_class = null)
    {
        $this->property         = $property;
        $this->attributed_class = $attributed_class ?? new AttributedClass($this->property->getDeclaringClass());
    }

    /**
     * Get property attributes
     *
     * @return array<object> Attribute
     */
    public function attributes() : array
    {
        return array_map(fn (\ReflectionAttribute $a) => $a->newInstance(), $this->property->getAttributes());
    }

    /**
     * Get property attribute.
     * If property attribute nothing, then check declaring class attribute and get.
     * If you don't want to check declaring class attribute, just given $check_declaring_class as false.
     *
     * @param string $attribute
     * @param bool $check_declaring_class
     * @return mixed Attribute
     */
    public function attribute(string $attribute, bool $check_declaring_class = true)
    {
        $attributes = $this->property->getAttributes($attribute);
        return !empty($attributes) ? $attributes[0]->newInstance() :
               ($check_declaring_class ? $this->attributed_class->attribute($attribute) : null)
        ;
    }

    /**
     * Get AttributedClass that is declaring class of the property.
     *
     * @return AttributedClass
     */
    public function declaringClass() : AttributedClass
    {
        return $this->attributed_class;
    }

    /**
     * Get the reflector of target property
     *
     * @return \ReflectionProperty
     */
    public function reflector() : \ReflectionProperty
    {
        return $this->property;
    }
}

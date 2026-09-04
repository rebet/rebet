<?php
declare(strict_types=1);

namespace Rebet\Attribute;

/**
 * Method attributes accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AttributedMethod
{
    /**
     * Reflection class of attribute target
     *
     * @var \ReflectionMethod
     */
    protected $method;

    /**
     * Attributed declaring class of the method.
     *
     * @var AttributedClass
     */
    protected $attributed_class;

    /**
     * Create method attributes accesser.
     *
     * @param string|\ReflectionMethod $method
     * @param string|object|\ReflectionClass|null $class
     * @return AttributedMethod
     */
    public static function of($method, $class = null) : AttributedMethod
    {
        if (is_string($method)) {
            $method = new \ReflectionMethod($class, $method);
        }
        return new AttributedMethod($method);
    }

    /**
     * Create a method attribute accessor
     *
     * @param \ReflectionMethod $method
     * @param AttributedClass|null $attributed_class
     */
    public function __construct(\ReflectionMethod $method, AttributedClass|null $attributed_class = null)
    {
        $this->method           = $method;
        $this->attributed_class = $attributed_class ?? new AttributedClass($this->method->getDeclaringClass());
    }

    /**
     * Get method attributes
     *
     * @return array<object> Attribute
     */
    public function attributes() : array
    {
        return array_map(fn (\ReflectionAttribute $a) => $a->newInstance(), $this->method->getAttributes());
    }

    /**
     * Get method attribute.
     * If method attribute nothing, then check declaring class attribute and get.
     * If you don't want to check declaring class attribute, just given $check_declaring_class as false.
     *
     * @param string $attribute
     * @param bool $check_declaring_class
     * @return mixed Attribute
     */
    public function attribute(string $attribute, bool $check_declaring_class = true)
    {
        $attributes = $this->method->getAttributes($attribute);
        return !empty($attributes) ? $attributes[0]->newInstance() :
               ($check_declaring_class ? $this->attributed_class->attribute($attribute) : null)
        ;
    }

    /**
     * Get AttributedClass that is declaring class of the method.
     *
     * @return AttributedClass
     */
    public function declaringClass() : AttributedClass
    {
        return $this->attributed_class;
    }

    /**
     * Get the reflector of target method
     *
     * @return \ReflectionMethod
     */
    public function reflector() : \ReflectionMethod
    {
        return $this->method;
    }
}

<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Annotations accessor utility
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Annotations
{
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }
    
    /**
     * Create class annotations accesser.
     *
     * @param string|object|\ReflectionClass $class
     * @return ClassAnnotations
     */
    public static function ofClass($class) : ClassAnnotations
    {
        return new ClassAnnotations(new AnnotationReader(), $class);
    }

    /**
     * Create method annotations accesser.
     *
     * @param string|\ReflectionMethod $method
     * @param string|object|\ReflectionClass|null $class
     * @return MethodAnnotations
     */
    public static function ofMethod($method, $class = null) : MethodAnnotations
    {
        if (is_string($method)) {
            $method = new \ReflectionMethod($class, $method);
        }
        return new MethodAnnotations(new AnnotationReader(), $method);
    }

    /**
     * Create property annotations accesser.
     *
     * @param string|\ReflectionProperty $property
     * @param string|object|\ReflectionClass|null $class
     * @return PropertyAnnotations
     */
    public static function ofProperty($property, $class = null) : PropertyAnnotations
    {
        if (is_string($property)) {
            $property = new \ReflectionProperty($class, $property);
        }
        return new PropertyAnnotations(new AnnotationReader(), $property);
    }
}

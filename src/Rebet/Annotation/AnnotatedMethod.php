<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Method annotations accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AnnotatedMethod
{
    /**
     * Annotation reader
     *
     * @var AnnotationReader
     */
    protected $reader = null;

    /**
     * Reflection class of annotation target
     *
     * @var \ReflectionMethod
     */
    protected $method = null;

    /**
     * Annotated declaring class of the method.
     *
     * @var AnnotatedClass
     */
    protected $annotated_class = null;

    /**
     * Create method annotations accesser.
     *
     * @param string|\ReflectionMethod $method
     * @param string|object|\ReflectionClass|null $class
     * @return AnnotatedMethod
     */
    public static function of($method, $class = null) : AnnotatedMethod
    {
        if (is_string($method)) {
            $method = new \ReflectionMethod($class, $method);
        }
        return new AnnotatedMethod($method);
    }

    /**
     * Create a method annotation accessor
     *
     * @param \ReflectionMethod $method
     * @param AnnotatedClass|null $annotated_class
     * @param AnnotationReader|null $reader
     */
    public function __construct(\ReflectionMethod $method, ?AnnotatedClass $annotated_class = null, ?AnnotationReader $reader = null)
    {
        $this->method          = $method;
        $this->reader          = $reader ?? AnnotationReader::getShared();
        $this->annotated_class = $annotated_class ?? new AnnotatedClass($this->method->getDeclaringClass(), $this->reader);
        AnnotationRegistry::registerUniqueLoader('class_exists');
    }

    /**
     * Get method annotations
     *
     * @return mixed Annotation
     */
    public function annotations() : array
    {
        return $this->reader->getMethodAnnotations($this->method);
    }

    /**
     * Get method annotation.
     * If method annotation nothing, then check declaring class annotation and get.
     * If you don't want to check declaring class annotation, just given $check_declaring_class as false.
     *
     * @param string $annotation
     * @param bool $check_declaring_class
     * @return mixed Annotation
     */
    public function annotation(string $annotation, bool $check_declaring_class = true)
    {
        return $this->reader->getMethodAnnotation($this->method, $annotation) ??
               ($check_declaring_class ? $this->annotated_class->annotation($annotation) : null)
        ;
    }

    /**
     * Get AnnotatedClass that is declaring class of the method.
     *
     * @return AnnotatedClass
     */
    public function declaringClass() : AnnotatedClass
    {
        return $this->annotated_class;
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

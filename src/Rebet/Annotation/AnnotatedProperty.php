<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Property annotations accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AnnotatedProperty
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
     * @var \ReflectionProperty
     */
    protected $property = null;

    /**
     * Annotated declaring class of the property.
     *
     * @var AnnotatedClass
     */
    protected $annotated_class = null;

    /**
     * Create property annotations accesser.
     *
     * @param string|\ReflectionProperty $property
     * @param string|object|\ReflectionClass|null $class
     * @return AnnotatedProperty
     */
    public static function of($property, $class = null) : AnnotatedProperty
    {
        if (is_string($property)) {
            $property = new \ReflectionProperty($class, $property);
        }
        return new AnnotatedProperty($property);
    }

    /**
     * Create a property annotation accessor.
     *
     * @param \ReflectionProperty $property
     * @param AnnotatedClass|null $annotated_class
     * @param AnnotationReader|null $reader
     */
    public function __construct(\ReflectionProperty $property, ?AnnotatedClass $annotated_class = null, ?AnnotationReader $reader = null)
    {
        $this->property        = $property;
        $this->reader          = $reader ?? AnnotationReader::getShared();
        $this->annotated_class = $annotated_class ?? new AnnotatedClass($this->property->getDeclaringClass(), $this->reader);
        AnnotationRegistry::registerUniqueLoader('class_exists');
    }

    /**
     * Get property annotations
     *
     * @return mixed @Annotation
     */
    public function annotations() : array
    {
        return $this->reader->getPropertyAnnotations($this->property);
    }

    /**
     * Get property annotation
     * If property annotation nothing, then check declaring class annotation and get.
     * If you don't want to check declaring class annotation, just given $check_declaring_class as false.
     *
     * @param string $annotation
     * @param bool $check_declaring_class
     * @return mixed @Annotation
     */
    public function annotation(string $annotation, bool $check_declaring_class = true)
    {
        return $this->reader->getPropertyAnnotation($this->property, $annotation) ??
               ($check_declaring_class ? $this->annotated_class->annotation($annotation) : null)
        ;
    }

    /**
     * Get AnnotatedClass that is declaring class of the property.
     *
     * @return AnnotatedClass
     */
    public function declaringClass() : AnnotatedClass
    {
        return $this->annotated_class;
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

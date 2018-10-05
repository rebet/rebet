<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class annotations accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AnnotatedClass
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
     * @var \ReflectionClass
     */
    protected $class = null;

    /**
     * Create class annotations accesser.
     *
     * @param string|object|\ReflectionClass $class
     * @return AnnotatedClass
     */
    public static function of($class) : AnnotatedClass
    {
        return new AnnotatedClass($class);
    }

    /**
     * Undocumented function
     * AnnotationRegistry
     *
     * @param string|object|\ReflectionClass $class
     * @param AnnotationReader|null $reader
     */
    public function __construct($class, ?AnnotationReader $reader = null)
    {
        $this->class  = $class instanceof \ReflectionClass ? $class : new \ReflectionClass($class) ;
        $this->reader = $reader ?? new AnnotationReader();
        AnnotationRegistry::registerUniqueLoader('class_exists');
    }

    /**
     * Get class annotations
     *
     * @return array [@Annotation, ...]
     */
    public function annotations() : array
    {
        return $this->reader->getClassAnnotations($this->class);
    }

    /**
     * Get class annotation
     *
     * @param string $annotation
     * @return mixed @Annotation
     */
    public function annotation(string $annotation)
    {
        return $this->reader->getClassAnnotation($this->class, $annotation);
    }

    /**
     * Get method annotation
     *
     * @param string $method
     * @return \AnnotatedMethod
     */
    public function method(string $method) : AnnotatedMethod
    {
        return new AnnotatedMethod($this->class->getMethod($method), $this, $this->reader);
    }

    /**
     * Get property annotation
     *
     * @param string $method
     * @return \AnnotatedMethod
     */
    public function property(string $property) : AnnotatedProperty
    {
        return new AnnotatedProperty($this->class->getProperty($property), $this, $this->reader);
    }
}

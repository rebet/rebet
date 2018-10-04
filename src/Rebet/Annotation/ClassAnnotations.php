<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Class annotations accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ClassAnnotations
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
     * @return ClassAnnotations
     */
    public static function of($class) : ClassAnnotations
    {
        return new ClassAnnotations($class);
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
     * @return \MethodAnnotations
     */
    public function method(string $method) : MethodAnnotations
    {
        return new MethodAnnotations($this->class->getMethod($method), $this->reader);
    }

    /**
     * Get property annotation
     *
     * @param string $method
     * @return \MethodAnnotations
     */
    public function property(string $property) : PropertyAnnotations
    {
        return new PropertyAnnotations($this->class->getProperty($property), $this->reader);
    }
}

<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;

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
     * Undocumented function
     *
     * @param AnnotationReader $reader
     * @param string|object|\ReflectionClass $class
     */
    public function __construct(AnnotationReader $reader, $class)
    {
        $this->reader = $reader;
        $this->class  = $class instanceof \ReflectionClass ? $class : new \ReflectionClass($class) ;
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
        return new MethodAnnotations($this->reader, $this->class->getMethod($method));
    }

    /**
     * Get property annotation
     *
     * @param string $method
     * @return \MethodAnnotations
     */
    public function property(string $property) : PropertyAnnotations
    {
        return new PropertyAnnotations($this->reader, $this->class->getProperty($property));
    }
}

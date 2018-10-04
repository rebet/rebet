<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Property annotations accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PropertyAnnotations
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
     * Create property annotations accesser.
     *
     * @param string|\ReflectionProperty $property
     * @param string|object|\ReflectionClass|null $class
     * @return PropertyAnnotations
     */
    public static function of($property, $class = null) : PropertyAnnotations
    {
        if (is_string($property)) {
            $property = new \ReflectionProperty($class, $property);
        }
        return new PropertyAnnotations($property);
    }

    /**
     * メソッドアノテーションアクセッサを構築します。
     *
     * @param \ReflectionProperty $property
     * @param AnnotationReader|null $reader
     */
    public function __construct(\ReflectionProperty $property, ?AnnotationReader $reader)
    {
        $this->property = $property;
        $this->reader   = $reader ?? new AnnotationReader();
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
     *
     * @param string $annotation
     * @return mixed @Annotation
     */
    public function annotation(string $annotation)
    {
        return $this->reader->getPropertyAnnotation($this->property, $annotation);
    }
}

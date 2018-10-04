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
     * メソッドアノテーションアクセッサを構築します。
     *
     * @param AnnotationReader $reader
     * @param \ReflectionProperty $property
     */
    public function __construct(AnnotationReader $reader, \ReflectionProperty $property)
    {
        $this->reader   = $reader;
        $this->property = $property;
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

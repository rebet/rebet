<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Method annotations accessor class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MethodAnnotations
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
     * メソッドアノテーションアクセッサを構築します。
     *
     * @param AnnotationReader $reader
     * @param \ReflectionMethod $method
     */
    public function __construct(AnnotationReader $reader, \ReflectionMethod $method)
    {
        $this->reader = $reader;
        $this->method  = $method;
    }

    /**
     * Get method annotations
     *
     * @return mixed @Annotation
     */
    public function annotations() : array
    {
        return $this->reader->getMethodAnnotations($this->method);
    }

    /**
     * Get method annotation
     *
     * @param string $annotation
     * @return mixed @Annotation
     */
    public function annotation(string $annotation)
    {
        return $this->reader->getMethodAnnotation($this->method, $annotation);
    }
}

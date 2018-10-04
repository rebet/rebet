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
     * Create method annotations accesser.
     *
     * @param string|\ReflectionMethod $method
     * @param string|object|\ReflectionClass|null $class
     * @return MethodAnnotations
     */
    public static function of($method, $class = null) : MethodAnnotations
    {
        if (is_string($method)) {
            $method = new \ReflectionMethod($class, $method);
        }
        return new MethodAnnotations($method);
    }

    /**
     * メソッドアノテーションアクセッサを構築します。
     *
     * @param \ReflectionMethod $method
     * @param AnnotationReader|null $reader
     */
    public function __construct(\ReflectionMethod $method, ?AnnotationReader $reader)
    {
        $this->method = $method;
        $this->reader = $reader ?? new AnnotationReader();
        AnnotationRegistry::registerUniqueLoader('class_exists');
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

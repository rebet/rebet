<?php
namespace Rebet\Annotation;

use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;

/**
 * Annotation Reader Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AnnotationReader extends DoctrineAnnotationReader
{
    /**
     * Shared AnnotationReader instance
     *
     * @var AnnotationReader
     */
    private static $shared = null;

    /**
     * Get the shared AnnotationReader instance.
     *
     * @return self
     */
    public static function getShared() : self
    {
        if (static::$shared === null) {
            static::$shared = new AnnotationReader();
        }

        return static::$shared;
    }
}

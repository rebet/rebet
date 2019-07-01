<?php
namespace Rebet\Database;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Describable;
use Rebet\Common\Popuratable;

/**
 * DTO (Data Transfer Object) Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Dto
{
    use Popuratable, Describable;

    /**
     * @var AnnotatedClass[]
     */
    protected static $annotated_class = [];

    /**
     * Create an DTO object
     */
    public function __construct()
    {
        $class = get_class($this);
        if (!isset(static::$annotated_class[$class])) {
            static::$annotated_class[$class] = new AnnotatedClass($class);
        }
    }

    /**
     * Get the annotated class.
     *
     * @return AnnotatedClass
     */
    protected function annotatedClass() : AnnotatedClass
    {
        return static::$annotated_class[get_class($this)];
    }
}

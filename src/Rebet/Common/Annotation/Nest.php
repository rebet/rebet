<?php
namespace Rebet\Common\Annotation;

/**
 * Nested Popuratable Annotation
 *
 * If the default value of the target property is an array, then nested as a list of object,
 * otherwise it is nested as a single object.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Nest
{
    /**
     * Class name of nested validatable object.
     *
     * @var string
     */
    public $value = null;
}

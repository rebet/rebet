<?php
namespace Rebet\Validation\Annotation;

/**
 * Label Validatable Annotation
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Label
{
    /**
     * Class name of nested validatable object.
     *
     * @var string
     */
    public $value = null;
}

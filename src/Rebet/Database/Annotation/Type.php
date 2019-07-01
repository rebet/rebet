<?php
namespace Rebet\Database\Annotation;

/**
 * Type Annotation
 *
 * Specifies the PHP data type to convert from PDO data.
 *
 * USAGE:
 *  - @Type("int")
 *  - @Type("DateTime")
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Type
{
    /**
     * Data type of PHP.
     *
     * @var string
     */
    public $value = null;
}

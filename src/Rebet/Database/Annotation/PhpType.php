<?php
namespace Rebet\Database\Annotation;

/**
 * PHP Type Annotation
 *
 * Specifies the PHP data type to convert from PDO data.
 * This annotation affects the value interpretation of the @Defaults annotation too.
 *
 * USAGE:
 *  - @PhpType("int")
 *  - @PhpType(DateTime::class) // with use Rebet\Tools\DateTime\DateTime
 *  - @PhpType('Rebet\Tools\DateTime\DateTime')
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @see Defaults
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class PhpType
{
    /**
     * Data type of PHP.
     *
     * @var string
     */
    public $value = null;
}

<?php
namespace Rebet\Database\Annotation;

/**
 * Defaults Annotation
 *
 * Set the default value of the target property.
 * This default value is referenced only at INSERT time and ignored at UPDATE time.
 *
 * USAGE:
 *  - @Defaults(1)
 *  - @Defaults('now') without @PhpType means default value is string of 'now'
 *  - @Defaults('now') with @PhpType(DateTime::class) will be Reflector::convert('now', DateTime::class)
 *  - @Defaults(1) with @PhpType(Gender::class) will be Reflector::convert(1, Gender::class)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @see PhpType
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Defaults
{
    /**
     * Default value
     *
     * @var mixed
     */
    public $value = null;
}

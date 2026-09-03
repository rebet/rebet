<?php
namespace Rebet\Database\Attribute;

/**
 * Defaults Attribute
 *
 * Set the default value of the target property.
 * This default value is referenced only at INSERT time and ignored at UPDATE time.
 *
 * USAGE:
 *  - #[Defaults(1)]
 *  - #[Defaults('now')] without property hint means default value is string of 'now'
 *  - #[Defaults('now')] with property hint `?DateTime` will be Reflector::convert('now', DateTime::class)
 *  - #[Defaults(1)] with property hint `?Gender` will be Reflector::convert(1, Gender::class)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Defaults
{
    /**
     * Default value
     *
     * @var mixed
     */
    public mixed $value;

    /**
     * Create Defaults attribute.
     *
     * @param mixed $value
     */
    public function __construct(mixed $value = null)
    {
        $this->value = $value;
    }
}

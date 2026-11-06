<?php

declare(strict_types=1);

namespace Rebet\Tools\Enum;

use Attribute;
use Rebet\Tools\Exception\LogicException;

/**
 * Enum Case Metadata Attribute
 *
 * Attach any named metadata to a case of enumeration.
 * The metadata can be read via `Enumerable::field()` or the method call of the meta name.
 *
 * Example:
 * enum AcceptStatus: int implements Enumeration {
 *     use Enumerable;
 *
 *     #[Meta(color: 'orange', icon: 'far fa-clock')]
 *     case Waiting = 1;
 * }
 *
 * => AcceptStatus::Waiting->field('color') // return 'orange'
 * => AcceptStatus::Waiting->color()        // return 'orange'
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Meta
{
    /**
     * Reserved names that can not be used as a metadata name.
     *
     * @var array<int, string>
     */
    public const RESERVED_NAMES = ['value', 'name'];

    /**
     * Metadata of the case.
     *
     * @var array<string, mixed>
     */
    public readonly array $data;

    /**
     * Create a metadata attribute.
     *
     * Note: The reserved names (value and name) can not be used as a metadata name,
     *       because they are reserved for the fields of the enumeration.
     *
     * @param  mixed          ...$data named metadata
     * @throws LogicException when the reserved name is given
     */
    public function __construct(mixed ...$data)
    {
        if ($reserved = array_intersect(self::RESERVED_NAMES, array_keys($data))) {
            throw new LogicException("Invalid meta name. Reserved name(" . implode(', ', $reserved) . ") can not be used as a metadata name.");
        }
        $this->data = $data;
    }
}

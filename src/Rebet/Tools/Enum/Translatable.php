<?php

declare(strict_types=1);

namespace Rebet\Tools\Enum;

use Attribute;
use Rebet\Tools\Exception\LogicException;

/**
 * Translatable Attribute
 *
 * Define the fields (the metadata and the label) that are translated by the translator, and the translation group.
 * This attribute decides all of the translation entrances of Enumerable
 * (translate(), label(), labels(), labelOf(), the translate option of fieldOf()/listOf()/nextOf() and the method call of the field).
 * The class that does not have this attribute is not translated at all.
 *
 * The attribute of the parent class is inherited, but the attribute of the child class takes precedence.
 *
 * Example:
 * #[Translatable(['label', 'label_short'], group: 'datetime')]
 * enum DayOfWeek: int implements Enumeration { ... }
 *
 * => DayOfWeek::Sunday->label()         // translated by the 'datetime' group
 * => DayOfWeek::Sunday->label_short()   // translated by the 'datetime' group
 * => DayOfWeek::Sunday->label_min()     // NOT translated (it is not given to the attribute)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Translatable
{
    /**
     * The default translation group.
     */
    public const DEFAULT_GROUP = 'enum';

    /**
     * The fields that are translated.
     *
     * @var array<int, string>
     */
    public readonly array $fields;

    /**
     * The translation group.
     *
     * @var string
     */
    public readonly string $group;

    /**
     * The lookup table of the fields. (for the fast check by has())
     *
     * @var array<string, int>
     */
    private readonly array $lookup;

    /**
     * Create a translatable attribute.
     *
     * Note: The identifiers ('value' and 'name', see Meta::RESERVED_NAMES) can not be translated.
     *
     * @param  array<int, string> $fields the fields to be translated (default: [])
     * @param  string             $group  the translation group (default: 'enum')
     * @throws LogicException     when the invalid field or group is given
     */
    public function __construct(array $fields = [], string $group = self::DEFAULT_GROUP)
    {
        if ($group === '') {
            throw new LogicException("Invalid translation group. The group should be a non empty string.");
        }
        foreach ($fields as $field) {
            if (!is_string($field)) {    // @phpstan-ignore function.alreadyNarrowedType
                throw new LogicException("Invalid translatable field. The field should be a string, " . get_debug_type($field) . " given.");
            }
            if (in_array($field, Meta::RESERVED_NAMES, true)) {
                throw new LogicException("Invalid translatable field. Reserved name({$field}) can not be translated.");
            }
        }

        $this->fields = array_values(array_unique($fields));
        $this->lookup = array_flip($this->fields);
        $this->group  = $group;
    }

    /**
     * Check whether the given field is translated.
     *
     * @param  string $field
     * @return bool
     */
    public function has(string $field): bool
    {
        return isset($this->lookup[$field]);
    }
}

<?php
declare(strict_types=1);

namespace Rebet\Database\Attribute;

/**
 * Table Attribute
 *
 * Specifies the RDB table name.
 * This attribute is not required, and the Inflector class determines the table name if no attribute is specified.
 *
 * USAGE:
 *  - #[Table("table_name")]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Table
{
    /**
     * Table name of RDB.
     *
     * @var string
     */
    public string $value;

    /**
     * Create Table attribute.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

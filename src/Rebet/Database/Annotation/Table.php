<?php
namespace Rebet\Database\Annotation;

/**
 * Table Annotation
 *
 * Specifies the RDB table name.
 * This annotation is not required, and the Inflector class determines the table name if no annotation is specified.
 *
 * USAGE:
 *  - @Table("table_name")
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Table
{
    /**
     * Table name of RDB.
     *
     * @var string
     */
    public $value = null;
}

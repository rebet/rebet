<?php
namespace Rebet\Database\Converter;

use Rebet\Database\Database;
use Rebet\Database\PdoParameter;

/**
 * Converter Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Converter
{
    /**
     * Convert given PHP type value to PDO data type.
     *
     * @param Database $db
     * @param mixed $value
     * @return PdoParameter
     */
    public function toPdoType(Database $db, $value) : PdoParameter;

    /**
     * Convert given PDO data type to PHP data type.
     *
     * @param Database $db
     * @param mixed $value
     * @param array $meta data of PDO column meta data. (default: [])
     * @param string|null $type that defined in property annotation. (default: null)
     * @return mixed
     */
    public function toPhpType(Database $db, $value, array $meta = [], ?string $type = null);
}

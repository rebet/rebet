<?php
namespace Rebet\Database\Compiler;

use Rebet\Database\Database;

/**
 * Compiler Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Compiler
{
    /**
     * Compile the given SQL template and params to PDO spec.
     *
     * @param Database $db
     * @param string $sql
     * @param array|object $params
     * @return string [string sql, array params]
     */
    public function compile(Database $db, string $sql, $params) : array;

    /**
     * Convert given parameter(key and value) to PDO spec.
     *
     * @param Database $db
     * @param string $key
     * @param mixed $value
     * @return array [string new_key, [string new_key => new_value]]
     */
    public function convertParam(Database $db, string $key, $value) : array;
}

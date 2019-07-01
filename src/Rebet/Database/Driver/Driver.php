<?php
namespace Rebet\Database\Driver;

/**
 * Driver Interface
 *
 * This interface covered PDO interfaces.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Driver
{
    /**
     * Initiates a transaction
     *
     * @return boolean
     */
    public function beginTransaction();

    /**
     * Commits a transaction
     *
     * @return boolean
     */
    public function commit();

    /**
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return string
     */
    public function errorCode();

    /**
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo();

    /**
     * Execute an SQL statement and return the number of affected rows
     *
     * @param string $statement
     * @return integer
     */
    public function exec($statement);

    /**
     * Retrieve a database connection attribute
     *
     * @param integer $attribute
     * @return mixed
     */
    public function getAttribute($attribute);

    /**
     * Return an array of available PDO drivers
     *
     * @return array
     */
    public static function getAvailableDrivers();

    /**
     * Checks if inside a transaction
     *
     * @return bool
     */
    public function inTransaction();

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @param string|null $name (default: null)
     * @return string
     */
    public function lastInsertId($name = null);

    /**
     * Prepares a statement for execution and returns a statement object
     *
     * @param string $statement
     * @param array $driver_options (default: [])
     * @return \PDOStatement
     */
    public function prepare($statement, $driver_options = []);

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param string $statement
     * @return \PDOStatement
     */
    public function query();

    /**
     * Quotes a string for use in a query
     *
     * @param string $string
     * @param integer $parameter_type (default: \PDO::PARAM_STR)
     * @return string
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR);

    /**
     * Rolls back a transaction
     *
     * @return boolean
     */
    public function rollBack();

    /**
     * Set an attribute
     *
     * @param integer $attribute
     * @param mixed $value
     * @return boolean
     */
    public function setAttribute($attribute, $value);
}

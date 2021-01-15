<?php
namespace Rebet\Database\Driver;

use PDO;
use Rebet\Database\Analysis\Analyzer;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\PdoParameter;

/**
 * PDO Driver Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Driver
{
    /**
     * Create driver.
     *
     * @param string $dsn
     * @param string|null $user (default: null)
     * @param string|null $password (default: null)
     * @param array $options  (default: [])
     * @return Driver
     */
    public static function create(string $dsn, ?string $user = null, ?string $password = null, array $options = []) : Driver;

    /**
     * Get PDO instance of this driver.
     *
     * @return \PDO
     */
    public function pdo() : \PDO;

    // /**
    //  * Get the driver name.
    //  *
    //  * @return string
    //  */
    // public function name() : string;

    /**
     * Get the server version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_SERVER_VERSION
     *
     * @return string
     */
    public function serverVersion() : string;

    /**
     * Get the client version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_CLIENT_VERSION, if the driver returned array then return combineded string `$key=$value;...`.
     *
     * @return string
     */
    public function clientVersion() : string;

    /**
     * Quotes a string for use in a query.
     *
     * @param string $string
     * @param int $parameter_type (default: \PDO::PARAM_STR)
     * @return string
     */
    public function quote(string $string, int $parameter_type = \PDO::PARAM_STR) : string;

    /**
     * Quote identifier names.
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier(string $identifier) : string;

    /**
     * Begin a transaction
     *
     * @return string executed SQL
     * @throws DatabaseException
     */
    public function begin() : string;

    /**
     * Set a transaction save point of given name.
     *
     * @param string $name of savepoint
     * @return string executed SQL
     * @throws DatabaseException
     */
    public function savepoint(string $name) : string;

    /**
     * Commit transaction.
     *
     * @return string executed SQL
     * @throws DatabaseException
     */
    public function commit() : string;

    /**
     * Rolls back a transaction
     *
     * @param string|null $savepoint (default: null)
     * @param boolean $quiet then this method ignore exception. (default: true)
     * @return string executed SQL
     * @throws DatabaseException
     */
    public function rollback(?string $savepoint = null, bool $quiet = true) : ?string;

    /**
     * Execute an SQL statement and return the number of affected rows
     *
     * @param string $sql
     * @return int
     * @throws DatabaseException
     */
    public function exec(string $sql) : int;

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $sql
     * @param array $driver_options (default: [])
     * @return \PDOStatement
     */
    public function prepare(string $sql, array $driver_options = []) : \PDOStatement;

    /**
     * Returns the ID of the last inserted row or sequence value of given name
     *
     * @param string|null $name (default: null)
     * @return string
     */
    public function lastInsertId(?string $name = null) : string;

    /**
     * Truncate given table data.
     * NOTE: This method reset identity number
     *
     * @param string $table_name
     * @param bool $with_vacuum if needed for sqlite (default: true)
     * @return string executed SQL
     */
    public function truncate(string $table_name, ?bool $with_vacuum = true) : string;

    /**
     * Close database connection.
     *
     * @return void
     */
    public function close() : void;

    /**
     * It checks the database connection is closed or not.
     *
     * @return boolean
     */
    public function closed() : bool;

    /**
     * Append limit offset partial SQL to given SQL.
     *
     * @param string $sql
     * @param int|null $limit
     * @param int|null $offset (default: null)
     * @return string
     */
    public function appendLimitOffset(string $sql, ?int $limit, ?int $offset = null) : string;

    /**
     * Append for update partial SQL to given SQL.
     *
     * @param string $sql
     * @return string
     * @throws DatabaseException if the database does not support `FOR UPDATE`.
     */
    public function appendForUpdate(string $sql) : string;

    /**
     * Convert given PHP type value to PDO data type.
     *
     * @param mixed $value
     * @return PdoParameter
     */
    public function toPdoType($value) : PdoParameter;

    /**
     * Convert given PDO data type to PHP data type.
     *
     * @param mixed $value
     * @param array $meta data of PDO column meta data. (default: [])
     * @param string|null $type that defined in property annotation. (default: null)
     * @return mixed
     */
    public function toPhpType($value, array $meta = [], ?string $type = null);

    /**
     * Get SQL analyzer of this database.
     *
     * @param string $sql
     * @return Analyzer
     */
    public function analyzer(string $sql) : Analyzer;
}

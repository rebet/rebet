<?php
namespace Rebet\Database\Compiler;

use Rebet\Database\Database;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\OrderBy;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\Statement;

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
     * Get compiler of given database
     *
     * @param Database $db
     * @return self
     */
    public static function of(Database $db) : self;

    /**
     * Compile the given SQL template and params to PDO spec (and return result adjust callback closure).
     *
     * @param string $sql
     * @param OrderBy|null $order_by (default: null)
     * @param array|object $params can be arrayable (default: [])
     * @param Pager|null $pager (default: null)
     * @param Cursor|null $cursor (default: null)
     * @return string [string sql, array params]
     */
    public function compile(string $sql, ?OrderBy $order_by = null, $params = [], ?Pager $pager = null, ?Cursor $cursor = null) : array;

    /**
     * Process a statement containing a result set and create a paginator object.
     * Also, if there is other processing necessary for page feed, it is done here.
     *
     * @param Statement $stmt
     * @param OrderBy|null $order_by
     * @param Pager $pager
     * @param Cursor|null $cursor (default: null)
     * @param int|null $total (default: null)
     * @param string $class (default: 'stdClass')
     * @return Paginator
     */
    public function paging(Statement $stmt, ?OrderBy $order_by, Pager $pager, ?Cursor $cursor = null, ?int $total = null, string $class = 'stdClass') : Paginator;

    /**
     * Convert given parameter(key and value) to PDO spec.
     *
     * @param string $key
     * @param mixed $value
     * @return array [string new_key, [string new_key => new_value]]
     */
    public function convertParam(string $key, $value) : array;

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
     * Create savepoint SQL.
     *
     * @param string $name of savepoint
     * @return string of savepoint SQL
     */
    public function savepoint(string $name) : string;

    /**
     * Create rollback to savepoint SQL.
     *
     * @param string $name of savepoint
     * @return string of rollback to savepoint SQL.
     */
    public function rollbackToSavepoint(string $name) : string;
}

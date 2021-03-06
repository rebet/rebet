<?php
namespace Rebet\Database\Compiler;

use Rebet\Database\Driver\Driver;
use Rebet\Database\OrderBy;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\Query;
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
     * Get compiler of given PDO driver
     *
     * @param Driver $driver
     * @return self
     */
    public static function of(Driver $driver) : self;

    /**
     * Compile the given SQL template and params to PDO spec (and return result adjust callback closure).
     *
     * @param string $sql
     * @param OrderBy|null $order_by (default: null)
     * @param array|object $params can be arrayable (default: [])
     * @param Pager|null $pager (default: null)
     * @param Cursor|null $cursor (default: null)
     * @return Query of full SQL sentence
     */
    public function compile(string $sql, ?OrderBy $order_by = null, $params = [], ?Pager $pager = null, ?Cursor $cursor = null) : Query;

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
     * @return Query of PDO spec placeholder SQL sentence
     */
    public function convertParam(string $key, $value) : Query;
}

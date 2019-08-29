<?php
namespace Rebet\Database\Compiler;

use PHPSQLParser\PHPSQLParser;
use Rebet\Common\Arrays;
use Rebet\Common\Strings;
use Rebet\Database\Database;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\OrderBy;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\Statement;

/**
 * Builtin Compiler Class
 *
 * This compiler will support cursor pagination.
 * However, in order to use it correctly, the following conditions must be met:
 *
 *  1. App of primary key element must be included in the 'Order By' conditions.
 *  2. All of 'Order By' columns must be included in SELECT phrase.
 *  3. All of 'Order By' columns must be able to access in WHERE (or HAVING) phrase.
 *  4．If the SQL top level WHERE (or HAVING) contains an OR, it must be enclosed in parenthes.
 *     ex) NG: SELECT * FROM user WHERE foo = 1 OR bar = 2
 *         OK: SELECT * FROM user WHERE (foo = 1 OR bar = 2)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinCompiler implements Compiler
{
    /**
     * {@inheritDoc}
     */
    public function compile(Database $db, string $sql, ?OrderBy $order_by = null, $params = [], ?Pager $pager = null, ?Cursor $cursor = null) : array
    {
        $pdo_params = [];
        foreach ($params as $key => $value) {
            if (!preg_match('/[a-zA-Z0-9_]+/', $key)) {
                throw DatabaseException::by("Invalid SQL query parameter key [ {$key} ], the key must be pattern of /[a-zA-Z0-9_]+/.");
            }
            if (Strings::contains($key, '__')) {
                throw DatabaseException::by("Invalid SQL query parameter key [ {$key} ], the key may not be contain '__'(combined two underscores).");
            }
            $key                   = ":{$key}";
            [$pdo_key, $pdo_param] = $this->convertParam($db, $key, $value);
            $pdo_params            = array_merge($pdo_params, $pdo_param);
            if ($pdo_key !== $key) {
                $sql = preg_replace("/{$key}(?=[^a-zA-Z0-9_]|$)/", $pdo_key, $sql);
            }
        }

        if ($order_by) {
            $cursor = $this->verify($pager, $cursor);

            if ($cursor === null) {
                $order_sql = $this->compileOrderBy($db, $order_by);
                $sql       = "{$sql} ORDER BY {$order_sql}";

                if ($pager) {
                    $offset = $this->offset($pager);
                    $limit  = $this->limit($pager);
                    $sql    = "{$sql} LIMIT {$limit} OFFSET {$offset}";
                }
            } else {
                $forward_feed             = $pager->page() >= $cursor->pager()->page() ;
                $near_by_first            = $pager->page() < abs($cursor->pager()->page() - $pager->page());
                $order_by                 = $forward_feed || $near_by_first ? $order_by : $order_by->reverse() ;
                $order_sql                = $this->compileOrderBy($db, $order_by);
                [$cursor_sql, $pdo_param] = $this->compileCursor($db, $order_by, $cursor, $forward_feed, $near_by_first);
                $pdo_params               = array_merge($pdo_params, $pdo_param);
                $offset                   = $this->offset($pager, $cursor, $forward_feed, $near_by_first);
                $limit                    = $this->limit($pager, $cursor, $forward_feed, $near_by_first);
                $parser                   = new PHPSQLParser($sql);
                $sql                      = isset($parser->parsed['WHERE']) || isset($parser->parsed['HAVING']) ? "{$sql} AND ({$cursor_sql})" : "{$sql} WHERE {$cursor_sql}" ;
                $sql                      = "{$sql} ORDER BY {$order_sql} LIMIT {$limit} OFFSET {$offset}";
            }
        }

        return [$sql, $pdo_params];
    }

    /**
     * Verify that we should be use cursor or not by the pager and cursor state.
     *
     * @param Pager $pager
     * @param Cursor|null $cursor
     * @return Cursor|null
     */
    protected function verify(?Pager $pager, ?Cursor $cursor) : ?Cursor
    {
        return $cursor && !$cursor->expired() && $cursor->pager()->verify($pager) ? $cursor : null ;
    }

    /**
     * Get offset count from given cursor (or first page) to given pager.
     *
     * @param Pager $page
     * @param Cursor|null $cursor
     * @param bool $forward_feed
     * @param bool $near_by_first
     * @return int
     */
    protected function offset(Pager $pager, ?Cursor $cursor = null, bool $forward_feed = true, bool $near_by_first = true) : int
    {
        $page = $pager->page();
        $size = $pager->size();
        if ($near_by_first || $cursor === null || !$pager->verify($cursor->pager())) {
            return $size * ($page - 1);
        }
        $cursor_page = $cursor->pager()->page();
        $distance    = abs($page - $cursor_page);
        return $forward_feed ? $size * $distance : $size * ($distance - 1) ;
    }

    /**
     * Get limit count based on given cursor (or offset from first page) for given pager (include next side pages).
     *
     * @param Pager $pager
     * @param Cursor|null $cursor
     * @param bool $forward_feed
     * @param bool $near_by_first
     * @return int
     */
    protected function limit(Pager $pager, ?Cursor $cursor = null, bool $forward_feed = true, bool $near_by_first = true) : int
    {
        $page            = $pager->page();
        $size            = $pager->size();
        $each_side       = $pager->eachSide();
        $base_limit      = $size;
        $next_side_count = $each_side === 0 ? 1 : max($each_side, $each_side * 2 - $page + 1);
        $next_side_limit = $pager->needTotal() ? 0 : $size * ($next_side_count - 1) ;
        if ($near_by_first || $cursor === null || !$pager->verify($cursor->pager())) {
            return $base_limit + $next_side_limit + 1;
        }
        $cursor_page = $cursor->pager()->page();
        if ($cursor_page - $page + $cursor->nextPageCount() >= $each_side) {
            $next_side_limit = 0;
        }
        return $forward_feed ? $base_limit + $next_side_limit + 1 : $base_limit + 1 ;
    }

    /**
     * Compile order by condition
     *
     * @param Database $db
     * @param OrderBy $order_by
     * @return string
     */
    protected function compileOrderBy(Database $db, OrderBy $order_by) : string
    {
        $order = [];
        foreach ($order_by as $col => $asc_desc) {
            $order[] = "{$col} {$asc_desc}";
        }
        return implode(', ', $order);
    }

    /**
     * Compile cursor condition
     *
     * @param Database $db
     * @param OrderBy $order_by
     * @param Cursor $cursor
     * @param bool $forward_feed
     * @param bool $near_by_first
     * @return array of [$where, $pdo_params]
     */
    protected function compileCursor(Database $db, OrderBy $order_by, Cursor $cursor, bool $forward_feed, bool $near_by_first) : array
    {
        $expressions = $forward_feed && !$near_by_first ? ['ASC' => '>', 'DESC' => '<'] : ['ASC' => '<', 'DESC' => '>'] ;
        $first       = true;

        $where       = "";
        $cursor_cols = array_keys($cursor->toArray());
        do {
            $where .= $first ? "(" : " OR (" ;
            $last   = array_pop($cursor_cols);
            $i      = 0;
            foreach ($cursor_cols as $col) {
                $where .= "{$col} = :cursor__{$i} AND ";
                $i++;
            }

            $expression  = $expressions[$order_by[$last]];
            $expression .= $first ? '=' : '' ;
            $first       = false;

            $where .= "{$last} {$expression} :cursor__{$i}";
            $where .= ")";
        } while (!empty($cursor_cols));
        return [$where, Arrays::pluck($cursor->toArray(), function ($i, $k, $v) use ($db) { return $db->convertToPdo($v); }, function ($i, $k, $v) { return ":cursor__{$i}"; })];
    }

    /**
     * {@inheritDoc}
     */
    public function paging(Database $db, Statement $stmt, ?OrderBy $order_by = null, Pager $pager, ?Cursor $cursor = null, ?int $total = null, string $class = 'stdClass') : Paginator
    {
        $cursor = $this->verify($pager, $cursor);

        $items         = [];
        $page          = $pager->page();
        $page_size     = $pager->size();
        $use_curosr    = $pager->useCursor();
        $forward_feed  = !$cursor || $page >= $cursor->pager()->page() ;
        $rs            = $forward_feed ? $stmt->all($class) : $stmt->all($class)->reverse() ;
        $cursor_data   = null;
        $count         = 0;
        foreach ($rs as $row) {
            if ($count < $page_size) {
                $items[] = $row;
            }
            if ($use_curosr && $count % $page_size === 0 && $count <= $page_size) {
                $cursor_data = $row;
            }
            $count++;
        }
        $next_page_count = max(max(0, ceil($count / $page_size) - 1), $cursor ? $cursor->pager()->page() - $page + $cursor->nextPageCount() : 0);
        if ($use_curosr) {
            $delta = $count <= $page_size ? 0 : 1 ;
            Cursor::create($order_by, $pager->next($delta), $cursor_data, $next_page_count - $delta)->save();
        }
        return new Paginator($items, $pager->eachSide(), $page_size, $page, $total, $next_page_count);
    }

    /**
     * {@inheritDoc}
     */
    public function convertParam(Database $db, string $key, $value) : array
    {
        $key = Strings::startsWith($key, ':') ? $key : ":{$key}" ;
        if (!is_array($value)) {
            return [$key, [$key => $db->convertToPdo($value)]];
        }

        $unfold_keys = [];
        $params      = [];
        $index       = 0;
        foreach ($value as $v) {
            $function = '?';
            if (is_array($v)) {
                [$function, $v] = $v;
            }
            if (Strings::contains($function, '?')) {
                $unfold_key          = "{$key}__{$index}";
                $params[$unfold_key] = $db->convertToPdo($v);
                $unfold_keys[]       = str_replace('?', $unfold_key, $function);
                $index++;
            } else {
                $unfold_keys[] = $function;
            }
        }
        return [join(', ', $unfold_keys), $params];
    }
}

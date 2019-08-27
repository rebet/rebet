<?php
namespace Rebet\Database\Compiler;

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
            $cursor = $cursor && !$cursor->expired() && $cursor->pager()->verify($pager) ? $cursor : null ;

            if ($cursor === null) {
                $order_sql = $this->compileOrderBy($db, $order_by);
                $sql       = "{$sql} ORDER BY {$order_sql}";

                if ($pager) {
                    $offset = $this->offset($pager);
                    $limit  = $this->limit($pager);
                    $sql    = "{$sql} OFFSET {$offset} LIMIT {$limit}";
                }
            } else {
                $is_backward              = $pager->page() < $cursor->pager()->page() ;
                $order_by                 = $is_backward ? $order_by->reverse() : $order_by ;
                $order_sql                = $this->compileOrderBy($db, $order_by);
                [$cursor_sql, $pdo_param] = $this->compileCursor($db, $order_by, $cursor, $is_backward);
                $pdo_params               = array_merge($pdo_params, $pdo_param);
                $offset                   = $this->offset($pager, $cursor);
                $limit                    = $this->limit($pager, $cursor);
                $sql                      = "SELECT * FROM ({$sql}) AS T WHERE {$cursor_sql} ORDER BY {$order_sql} OFFSET {$offset} LIMIT {$limit}";
            }
        }

        return [$sql, $pdo_params];
    }

    /**
     * Get offset count from given cursor (or first page) to given pager.
     *
     * @param Pager $page
     * @param Cursor|null $cursor (default: null)
     * @return int
     */
    protected function offset(Pager $pager, ?Cursor $cursor = null) : int
    {
        $page = $pager->page();
        $size = $pager->size();
        if ($cursor === null || !$pager->verify($cursor->pager())) {
            return $size * ($page - 1);
        }
        $cursor_page = $cursor->pager()->page();
        $is_backward = $page < $cursor_page ;
        $distance    = abs($page - $cursor_page);
        return $is_backward ? 0 : $size * $distance ;
    }

    /**
     * Get limit count based on given cursor (or offset from first page) for given pager (include next side pages).
     *
     * @param Cursor|null $cursor (default: null)
     * @return int
     */
    protected function limit(Pager $pager, ?Cursor $cursor = null) : int
    {
        $page            = $pager->page();
        $size            = $pager->size();
        $each_side       = $pager->eachSide();
        $base_limit      = $size;
        $next_side_count = max($each_side, $each_side * 2 + 1 - $page) - 1;
        $next_side_limit = $pager->need_total ? 0 : $size * $next_side_count ;
        if ($cursor === null || !$pager->verify($cursor->pager())) {
            return $base_limit + $next_side_limit + 1;
        }
        $cursor_page = $cursor->pager()->page();
        $is_backward = $page < $cursor_page ;
        if ($cursor_page - $page + $cursor->nextPageCount() >= $each_side) {
            $next_side_limit = 0;
        }
        $distance_limit = $size * abs($page - $cursor_page);
        return $is_backward ? $distance_limit + 1 : $base_limit + $distance_limit + $next_side_limit + 1 ;
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
        foreach ($order_by as $col => $order) {
            $order[] = "{$col} {$order}";
        }
        return implode(', ', $order);
    }

    /**
     * Compile cursor condition
     *
     * @param Database $db
     * @param OrderBy $order_by
     * @param Cursor $cursor
     * @param boolean $is_backward
     * @return array of [$where, $pdo_params]
     */
    protected function compileCursor(Database $db, OrderBy $order_by, Cursor $cursor, bool $is_backward) : array
    {
        $expressions = $is_backward ? ['ASC' => '<', 'DESC' => '>'] : ['ASC' => '>', 'DESC' => '<'] ;
        $include     = true;

        $where  = " AND (1 <> 1";
        $cursor = array_keys($cursor->toArray());
        do {
            $where .= " OR (";
            $last   = array_pop($cursor);
            $i      = 0;
            foreach ($cursor as $col) {
                $where .= "{$col} = :cursor__{$i} AND ";
                $i++;
            }

            $expression  = $expressions[$order_by[$last]];
            $expression .= $include ? '=' : '' ;
            $include     = false;

            $where .= "{$last} {$expression} :cursor__{$i}";
            $where .= ")";
        } while (empty($cursor));
        $where .= ")";
        return [$where, Arrays::pluck($cursor, function ($i, $k, $v) use ($db) { return $db->convertToPdo($v); }, function ($i, $k, $v) { return "cursor__{$i}"; })];
    }

    /**
     * {@inheritDoc}
     */
    public function paging(Database $db, Statement $stmt, ?OrderBy $order_by = null, Pager $pager, ?Cursor $cursor = null, ?int $total = null, string $class = 'stdClass') : Paginator
    {
        $cursor = $cursor && !$cursor->expired() && $cursor->pager()->verify($pager) ? $cursor : null ;

        $items       = [];
        $page        = $pager->page();
        $page_size   = $pager->size();
        $use_curosr  = $pager->useCursor();
        $is_forward  = !$cursor || $page >= $cursor->pager()->page() ;
        $rs          = $is_forward ? $stmt->all($class) : $stmt->all($class)->reverse() ;
        $cursor_data = null;
        $count       = 0;
        foreach ($stmt as $row) {
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
        return new Paginator($items, $page_size, $page, $total, $next_page_count);
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

    // /**
    //  * Create next cursor SQL where.
    //  *
    //  * @param bool $include the cursor data.
    //  * @param array $expression map for order.
    //  * @return array [string $where, array $value_map]
    //  */
    // protected function build(bool $include, array $expression) : array
    // {
    //     $where  = " AND (1 <> 1";
    //     $cursor = $this->cursor;
    //     do {
    //         $where .= " OR (";
    //         $last   = array_pop($cursor);
    //         $i      = 0;
    //         foreach ($cursor as [$col, $order, $value]) {
    //             $where .= "{$col} = :cursor__{$i} AND ";
    //             $i++;
    //         }

    //         $expression  = $expression[$last[Cursor::ORDER]];
    //         $expression .= $include ? '=' : '' ;
    //         $include     = false;

    //         $where .= "{$last[Cursor::COLUMN]} {$expression} :cursor__{$i}";
    //         $where .= ")";
    //     } while (empty($cursor));
    //     $where .= ")";
    //     return [$where, Arrays::pluck($this->cursor, Cursor::VALUE, function ($i, $k, $v) { return "cursor__{$i}"; })];
    // }
}

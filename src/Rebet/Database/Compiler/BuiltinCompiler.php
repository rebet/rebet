<?php
namespace Rebet\Database\Compiler;

use Rebet\Database\Analysis\Analyzer;
use Rebet\Database\Database;
use Rebet\Database\Driver\Driver;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\Expression;
use Rebet\Database\OrderBy;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\Query;
use Rebet\Database\Statement;
use Rebet\Tools\Template\Letterpress;
use Rebet\Tools\Utility\Strings;

/**
 * Builtin Compiler Class
 *
 * This compiler will support cursor pagination.
 * However, in order to use it correctly, the following conditions must be met:
 *
 *  1. App of primary key element must be included in the 'Order By' conditions.
 *  2. All of 'Order By' columns must be included in SELECT phrase.
 *  3．If the SQL top level WHERE (or HAVING) contains an OR, it must be enclosed in parenthes.
 *     ex) NG: SELECT * FROM user WHERE foo = 1 OR bar = 2
 *         OK: SELECT * FROM user WHERE (foo = 1 OR bar = 2)
 *
 * And this compiler also support `Letterpress` template format.
 * So, you can use all `Letterpress` tags like 'if' and 'for' (and extended tags) with given params in your SQL.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinCompiler implements Compiler
{
    /**
     * PDO Driver
     *
     * @var Driver
     */
    protected $driver;

    /**
     * Create Builtin Conpiler of given PDO driver.
     *
     * @param Database $db
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritDoc}
     */
    public static function of(Driver $driver) : Compiler
    {
        return new static($driver);
    }

    /**
     * {@inheritDoc}
     */
    public function compile(string $sql, ?OrderBy $order_by = null, $params = [], ?Pager $pager = null, ?Cursor $cursor = null) : Query
    {
        // ---------------------------------------------------------------------
        // Resolve embedded `Letterpress` tags
        // ---------------------------------------------------------------------
        $sql = Letterpress::of($sql)->with($params ?? [])->render();

        // ---------------------------------------------------------------------
        // Check params key format, non use params and resolve multi placeholder
        // ---------------------------------------------------------------------
        $params = $params ?? [];
        foreach ($params as $key => $value) {
            if (!preg_match('/[a-zA-Z0-9_]+/', $key)) {
                throw new DatabaseException("Invalid SQL query parameter key [ {$key} ], the key must be pattern of /[a-zA-Z0-9_]+/.");
            }
            if (Strings::contains($key, '__')) {
                throw new DatabaseException("Invalid SQL query parameter key [ {$key} ], the key may not be contain '__'(combined two underscores).");
            }
            $holder = ":{$key}";
            $count  = preg_match_all('/'.$holder.'([^a-zA-Z0-9_]|$)/', $sql);
            if ($count === 0) {
                unset($params[$key]);
                continue;
            }
            if ($count === 1) {
                continue;
            }
            for ($i = 0 ; $i < $count ; $i++) {
                $sql                    = preg_replace('/('.$holder.')([^a-zA-Z0-9_]|$)/', '$1__'.$i.'$2', $sql, 1);
                $params["{$key}__{$i}"] = $value;
            }
            unset($params[$key]);
        }

        // ---------------------------------------------------------------------
        // Resolve array value placeholder and values convert to PDO params
        // ---------------------------------------------------------------------
        $pdo_params = [];
        foreach ($params as $key => $value) {
            $key        = ":{$key}";
            $new_param  = $this->convertParam($key, $value);
            $pdo_params = array_merge($pdo_params, $new_param->params());
            if ($new_param->sql() !== $key) {
                $sql = preg_replace("/{$key}(?=[^a-zA-Z0-9_]|$)/", $new_param->sql(), $sql);
            }
        }

        // ---------------------------------------------------------------------
        // Resolve Order By / Pager / Cursor
        // ---------------------------------------------------------------------
        if ($order_by) {
            $cursor = $this->verify($pager, $cursor);

            if ($cursor === null) {
                $order_sql = $this->compileOrderBy($order_by);
                $sql       = "{$sql} ORDER BY {$order_sql}";

                if ($pager) {
                    $offset = $this->offset($pager);
                    $limit  = $this->limit($pager);
                    $sql    = $this->driver->appendLimitOffset($sql, $limit, $offset);
                }
            } else {
                $analyzer      = $this->driver->analyzer($sql);
                $forward_feed  = $pager->page() >= $cursor->pager()->page() ;
                $near_by_first = $pager->page() < abs($cursor->pager()->page() - $pager->page());
                $order_by      = $forward_feed ? $order_by : ($near_by_first ? $order_by : $order_by->reverse()) ;
                $order_sql     = $this->compileOrderBy($order_by);
                $cursor_query  = $this->compileCursor($analyzer, $order_by, $cursor, $forward_feed, $near_by_first);
                $cursor_where  = $cursor_query->sql();
                $pdo_params    = array_merge($pdo_params, $cursor_query->params());
                $offset        = $this->offset($pager, $cursor, $forward_feed, $near_by_first);
                $limit         = $this->limit($pager, $cursor, $forward_feed, $near_by_first);
                $sql           = $analyzer->isUnion() ? "SELECT * FROM ({$sql}) AS T" : $sql ;
                if ($analyzer->hasGroupBy()) {
                    $sql = $analyzer->hasHaving() ? "{$sql} AND ({$cursor_where})" : "{$sql} HAVING {$cursor_where}" ;
                } else {
                    $sql = $analyzer->hasWhere() || $analyzer->hasHaving() ? "{$sql} AND ({$cursor_where})" : "{$sql} WHERE {$cursor_where}" ;
                }
                $sql = $this->driver->appendLimitOffset("{$sql} ORDER BY {$order_sql}", $limit, $offset);
            }
        }

        return $this->driver->sql($sql, $pdo_params);
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
     * @param OrderBy $order_by
     * @return string
     */
    protected function compileOrderBy(OrderBy $order_by) : string
    {
        $order = [];
        foreach ($order_by as $col => $asc_desc) {
            $order[] = "{$this->driver->quoteIdentifier($col)} {$asc_desc}";
        }
        return implode(', ', $order);
    }

    /**
     * Compile cursor condition
     *
     * @param Analyzer $analyzer of sql
     * @param OrderBy $order_by
     * @param Cursor $cursor
     * @param bool $forward_feed
     * @param bool $near_by_first
     * @return Query of partial where SQL sentence
     */
    protected function compileCursor(Analyzer $analyzer, OrderBy $order_by, Cursor $cursor, bool $forward_feed, bool $near_by_first) : Query
    {
        $expressions = $forward_feed ? ['ASC' => '>', 'DESC' => '<'] :  ($near_by_first ? ['ASC' => '<', 'DESC' => '>'] :  ['ASC' => '>', 'DESC' => '<']) ;
        $first       = true;

        $where        = "";
        $params       = [];
        $cursor_cols  = array_keys($cursor->toArray());
        $has_group_by = $analyzer->hasGroupBy();
        $i            = 0;
        do {
            $where .= $first ? "(" : " OR (" ;
            $last   = array_pop($cursor_cols);
            $j      = 0;
            foreach ($cursor_cols as $col) {
                $real_col     = $has_group_by ? $col : $analyzer->extractAliasSelectColumn($col) ;
                $key          = ":cursor__{$i}__{$j}";
                $params[$key] = $params[":cursor__0__{$j}"] ?? $this->driver->toPdoType($cursor[$col]);

                $where .= "{$this->driver->quoteIdentifier($real_col)} = {$key} AND ";
                $j++;
            }

            $expression  = $expressions[$order_by[$last]];
            $expression .= $first ? '=' : '' ;
            $first       = false;

            $real_last    = $has_group_by ? $last : $analyzer->extractAliasSelectColumn($last) ;
            $key          = ":cursor__{$i}__{$j}";
            $params[$key] = $params[":cursor__0__{$j}"] ?? $this->driver->toPdoType($cursor[$last]);

            $where .= "{$this->driver->quoteIdentifier($real_last)} {$expression} {$key}";
            $where .= ")";
            $i++;
        } while (!empty($cursor_cols));
        return $this->driver->sql($where, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function paging(Statement $stmt, ?OrderBy $order_by = null, Pager $pager, ?Cursor $cursor = null, ?int $total = null, string $class = 'stdClass') : Paginator
    {
        $cursor = $this->verify($pager, $cursor);

        $items         = [];
        $page          = $pager->page();
        $page_size     = $pager->size();
        $use_curosr    = $pager->useCursor();
        $forward_feed  = !$cursor || $page >= $cursor->pager()->page() ;
        $near_by_first = !$cursor || $page < abs($cursor->pager()->page() - $page);
        $rs            = $forward_feed ? $stmt->all($class) : ($near_by_first ? $stmt->all($class) : $stmt->all($class)->reverse()) ;
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
        if ($pager->needTotal()) {
            $last_page       = intval(floor($total / $page_size) + ($total % $page_size == 0 ? 0 : 1));
            $last_page       = $last_page === 0 ? 1 : $last_page ;
            $page            = $last_page < $page ? $last_page : $page ;
            $next_page_count = $last_page - $page;
        } else {
            $next_page_count = max(max(0, ceil($count / $page_size) - 1), $cursor ? $cursor->pager()->page() - $page + $cursor->nextPageCount() : 0);
        }
        if ($use_curosr && $count !== 0) {
            $delta = $count <= $page_size ? 0 : 1 ;
            Cursor::create($order_by, $pager->next($delta), $cursor_data, $next_page_count - $delta)->save();
        }
        return new Paginator($items, $pager->eachSide(), $page_size, $page, $total, $next_page_count);
    }

    /**
     * {@inheritDoc}
     */
    public function convertParam(string $key, $value) : Query
    {
        $key = Strings::startsWith($key, ':') ? $key : ":{$key}" ;
        if ($value instanceof Expression) {
            return $value->compile($this->driver, $key);
        }
        if (!is_array($value)) {
            return $this->driver->sql($key, [$key => $this->driver->toPdoType($value)]);
        }

        $unfold_keys = [];
        $params      = [];
        $index       = 0;
        foreach ($value as $v) {
            $new_key = "{$key}__{$index}";
            $index++;

            if ($v instanceof Expression) {
                $expression    = $v->compile($this->driver, $new_key);
                $unfold_keys[] = $expression->sql();
                $params        = array_merge($params, $expression->params());
                continue;
            }

            $unfold_keys[]    = $new_key;
            $params[$new_key] = $this->driver->toPdoType($v);
        }
        return $this->driver->sql(join(', ', $unfold_keys), $params);
    }
}

<?php
namespace Rebet\Database;

use Rebet\Database\Driver\Driver;
use Rebet\Tools\Utility\Strings;

/**
 * Query Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Query
{
    /**
     * The database driver for this SQL query.
     */
    protected Driver $driver;

    /**
     * Full or partial SQL sentence.
     *
     * @var string
     */
    protected string $sql = '';

    /**
     * Full or partial SQL parameters
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Create full or partial SQL.
     *
     * @param Driver $driver
     * @param string $sql
     * @param array $params (default: [])
     */
    public function __construct(Driver $driver, string $sql, array $params = [])
    {
        $this->driver = $driver;
        $this->sql    = $sql;
        $this->params = $params;
    }

    /**
     * Get the database driver for this SQL query.
     *
     * @return Driver
     */
    public function driver() : Driver
    {
        return $this->driver;
    }

    /**
     * Get full or partial SQL sentence.
     *
     * @return string
     */
    public function sql() : string
    {
        return $this->sql;
    }

    /**
     * Get full or partial SQL parameters
     *
     * @return array
     */
    public function params() : array
    {
        return $this->params;
    }

    /**
     * Append where condition to this query.
     *
     * @param string|array $where
     * @param array $params (default: [])
     * @return self
     */
    public function appendWhere($where, array $params = []) : self
    {
        $this->sql    = $this->driver->appendWhere($this->sql, $where);
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * Append limit offset partial SQL to this query.
     *
     * @param int|null $limit
     * @param int|null $offset (default: null)
     * @return self
     */
    public function appendLimitOffset(?int $limit, ?int $offset = null) : self
    {
        $this->sql = $this->driver->appendLimitOffset($this->sql, $limit, $offset);
        return $this;
    }

    /**
     * Append for update partial SQL to this query.
     *
     * @return self
     */
    public function appendForUpdate() : self
    {
        $this->sql = $this->driver->appendForUpdate($this->sql);
        return $this;
    }

    /**
     * SQL as a where sentence.
     *
     * @return string
     */
    public function asWhere() : string
    {
        return empty($this->sql) ? '' : " WHERE {$this->sql}" ;
    }

    /**
     * Emulate SQL for logging and exception message.
     * You should not use this method other than to emulate sql for log output.
     *
     * @return string
     */
    public function emulate() : string
    {
        $sql = $this->sql;
        foreach ($this->params as $key => $value) {
            $value = is_array($value) ? join(', ', array_map(function ($v) { return $this->convertToSql($v); }, $value)) : $this->convertToSql($value) ;
            $sql   = preg_replace("/".preg_quote($key, '/')."(?=[^a-zA-Z0-9_]|$)/", $value, $sql);
        }

        return "/* Emulated SQL */ ".$sql;
    }

    /**
     * Convert given value to SQL string for SQL emulate.
     * You should not use this method other than to emulate sql for log output.
     *
     * @param mixed $value
     * @return string
     */
    protected function convertToSql($value) : string
    {
        $param = $value instanceof PdoParameter ? $value : $this->driver->toPdoType($value);
        if ($param->value === null) {
            return 'NULL';
        }
        if ($param->type === \PDO::PARAM_LOB) {
            return 'NULL/*LOB('.strlen($param->value).')*/';
        }
        return $this->driver->quote($param->value, $param->type);
    }

    /**
     * Convert to exception/log message string
     */
    public function toString()
    {
        return "Query[".$this->driver->name()."] {\n".
               "  sql: ".Strings::indent(Strings::stringify($this->sql), " ", 4).
               "  params: ".Strings::indent(Strings::stringify($this->params), " ", 4).
               "}"
               ;
    }
}

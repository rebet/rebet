<?php
namespace Rebet\Database;

use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Compiler\Compiler;
use Rebet\Database\Converter\BuiltinConverter;
use Rebet\Database\Converter\Converter;
use Rebet\Database\DataModel\Entity;
use Rebet\Database\Driver\Driver;
use Rebet\Database\Event\Created;
use Rebet\Database\Event\Creating;
use Rebet\Database\Event\Deleted;
use Rebet\Database\Event\Deleting;
use Rebet\Database\Event\Updated;
use Rebet\Database\Event\Updating;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\DateTime\DateTime;
use Rebet\Event\Event;

/**
 * Database Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Database
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'compiler'    => BuiltinCompiler::class,
            'converter'   => BuiltinConverter::class,
            'log_handler' => null, // function(string $db_name, string $sql, array $params = []) {}
        ];
    }

    /**
     * The name of this database (alias ​​for classification).
     *
     * @var string
     */
    protected $name = null;

    /**
     * The database driver that supported PDO interfaces.
     *
     * @var Driver
     */
    protected $driver = null;

    /**
     * Status that logging or not.
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * Emulate SQL or not for debug logging.
     *
     * @var boolean
     */
    protected $emulated_sql_log = true;

    /**
     * SQL logging handler for debug
     *
     * @var \Closure function(string $db_name, string $sql, array $params = []) {}
     */
    protected $log_handler = null;

    /**
     * Compiler of SQL template and params.
     *
     * @var Compiler
     */
    protected $compiler = null;

    /**
     * Converter of PDO/PHP values.
     *
     * @var Converter
     */
    protected $converter = null;

    /**
     * Create database instance using given driver.
     *
     * @param string $name of this database (alias ​​for classification)
     * @param Driver $driver
     * @param bool $debug (default: false)
     * @param bool $emulated_sql_log (default: true)
     * @param callable|null $log_handler function(string $name, string $sql, array $params = []) (default: depend on configure)
     * @param Converter|null $converter (default: depend on configure)
     * @param Compiler|null $compiler (default: depend on configure)
     */
    public function __construct(string $name, Driver $driver, bool $debug = false, bool $emulated_sql_log = true, ?callable $log_handler = null, ?Converter $converter = null, ?Compiler $compiler = null)
    {
        $this->name             = $name;
        $this->driver           = $driver;
        $this->driver->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->driver->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->driver->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->debug            = $debug;
        $this->emulated_sql_log = $emulated_sql_log;
        $this->log_handler      = $log_handler ? \Closure::fromCallable($log_handler) : static::config('log_handler') ;
        $this->converter        = $converter ?? static::configInstantiate('converter');
        $this->compiler         = $compiler ?? static::configInstantiate('compiler');
    }

    /**
     * Get the name of this database.
     *
     * @return string
     */
    public function name() : string
    {
        return $this->name;
    }

    /**
     * Get the PDO driver name of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_DRIVER_NAME
     *
     * @return string
     */
    public function driverName() : string
    {
        return $this->driver->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Get the server version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_SERVER_VERSION
     *
     * @return string
     */
    public function serverVersion() : string
    {
        return $this->driver->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get the client version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_CLIENT_VERSION
     *
     * @return string
     */
    public function clientVersion() : string
    {
        return $this->driver->getAttribute(\PDO::ATTR_CLIENT_VERSION);
    }

    /**
     * Get the database driver that supported PDO interfaces.
     *
     * @return Driver
     */
    public function driver() : Driver
    {
        return $this->driver;
    }

    /**
     * Get the compiler of this database.
     *
     * @return Compiler
     */
    public function compiler() : Compiler
    {
        return $this->compiler;
    }

    /**
     * Get the converter of this database.
     *
     * @return Converter
     */
    public function converter() : Converter
    {
        return $this->converter;
    }

    /**
     * Switch on/off to log output.
     *
     * @param bool $debug (default: true)
     * @param bool|null $emulated_sql_log (default: null for not change)
     * @return self
     */
    public function debug(bool $debug = true, ?bool $emulated_sql_log = null) : self
    {
        $this->debug            = $debug;
        $this->emulated_sql_log = $emulated_sql_log ?? $this->emulated_sql_log;
        return $this;
    }

    /**
     * Output SQL log.
     *
     * @param string $sql
     * @param array $params (default: [])
     * @return void
     */
    public function log(string $sql, array $params = []) : void
    {
        if ($this->debug) {
            call_user_func($this->log_handler, $this->name, ...$this->convertForMessage($sql, $params));
        }
    }

    /**
     * Create Database exception from given information.
     *
     * @param array|\PDOException $error
     * @param string|null $sql (default: null)
     * @param array $param (default: [])
     * @return DatabaseException
     */
    public function exception($error, ?string $sql = null, array $params = []) : DatabaseException
    {
        return DatabaseException::from($this, $error, ...$this->convertForMessage($sql, $params));
    }

    /**
     * Convert given SQL and params for log output and exception message.
     * This method emulate SQL when emulated_sql_log is true.
     *
     * @param string|null $sql
     * @param array $params (default: [])
     * @return array [emulated_sql] or [sql, params]
     */
    protected function convertForMessage(?string $sql, array $params = []) : array
    {
        if ($sql === null) {
            return [null];
        }
        return $this->emulated_sql_log ? [$this->emulate($sql, $params)] : [$sql, $params] ;
    }

    /**
     * Emulate given SQL for logging and exception message.
     * You should not use this method other than to emulate sql for log output.
     *
     * @param string $sql
     * @param array $params (default: [])
     * @return string
     */
    protected function emulate(string $sql, array $params = []) : string
    {
        foreach ($params as $key => $value) {
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
        $param = $value instanceof PdoParameter ? $value : $this->converter->toPdoType($this, $value);
        if ($param->value === null) {
            return 'NULL';
        }
        return $this->driver->quote($param->value, $param->type);
    }

    /**
     * Convert given PHP type value to PDO data type using converter.
     *
     * @param mixed $value
     * @return PdoParameter
     */
    public function convertToPdo($value) : PdoParameter
    {
        return $this->converter->toPdoType($this, $value);
    }

    /**
     * Convert given PDO type value to PHP data type using converter.
     *
     * @param mixed $value
     * @param array $meta data of PDO column meta data. (default: [])
     * @param string|null $type that defined in property annotation. (default: null)
     * @return mixed
     */
    public function convertToPhp($value, array $meta = [], ?string $type = null)
    {
        return $this->converter->toPhpType($this, $value, $meta, $type);
    }

    /**
     * Start the transaction.
     *
     * @return self
     * @throws DatabaseException|\PDOException
     */
    public function begin() : self
    {
        if (!$this->driver->beginTransaction()) {
            throw $this->exception($this->driver->errorInfo());
        }
        $this->log("BEGIN");
        return $this;
    }

    /**
     * Set a transaction save point of given name.
     *
     * @param string $name of save point
     * @return self
     * @throws DatabaseException|\PDOException
     */
    public function savepoint(string $name) : self
    {
        $sql = "SAVEPOINT {$name}";
        $this->driver->exec($sql);
        $this->log($sql);
        return $this;
    }

    /**
     * Rolls back a transaction
     *
     * @param string|null $savepoint (default: null)
     * @param boolean $quiet then this method ignore exception. (default: true)
     * @return self
     * @throws DatabaseException|\PDOException
     */
    public function rollback(?string $savepoint = null, bool $quiet = true) : self
    {
        try {
            $sql = $savepoint ? "ROLLBACK TO SAVEPOINT {$savepoint}" : null ;
            if (!($sql ? $this->driver->exec($sql) : $this->driver->rollBack())) {
                throw $this->exception($this->driver->errorInfo(), $sql);
            }
            $this->log($sql ?? "ROLLBACK");
        } catch (\Exception $e) {
            if (!$quiet) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * Commit the transaction.
     *
     * @return self
     * @throws DatabaseException|\PDOException
     */
    public function commit() : self
    {
        if (!$this->driver->commit()) {
            throw $this->exception($this->driver->errorInfo());
        }
        $this->log("COMMIT");
        return $this;
    }

    /**
     * Start a transaction, execute callback then commit.
     * NOTE: If an exception thrown then rollback.
     *
     * @param \Closure $callback function(Database $db) { ... }
     * @return self
     * @throws \Throwable
     */
    public function transaction(\Closure $callback) : self
    {
        try {
            $this->begin();
            $callback($this);
            $this->commit();
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * Returns the ID of the last inserted row or sequence value of given name
     *
     * @param string|null $name (default: null)
     * @return string
     */
    public function lastInsertId(?string $name = null) : string
    {
        return $this->driver->lastInsertId($name);
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $sql
     * @return Statement
     */
    protected function prepare(string $sql) : Statement
    {
        try {
            $stmt = $this->driver->prepare($sql, [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // \PDO::ATTR_CURSOR             => \PDO::CURSOR_SCROLL,
            ]);
            if (!$stmt) {
                throw $this->exception($this->driver->errorInfo(), $sql);
            }
            return new Statement($this, $stmt);
        } catch (\PDOException $e) {
            throw $this->exception($e, $sql);
        }
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param Pager|null $pager (default: null)
     * @param Cursor|null $cursor (default: null)
     * @return Statement
     */
    protected function _query(string $sql, $order_by = null, $params = [], ?Pager $pager = null, ?Cursor $cursor = null) : Statement
    {
        [$sql, $params] = $this->compiler->compile($this, $sql, OrderBy::valueOf($order_by), $params, $pager, $cursor);
        return $this->prepare($sql)->execute($params);
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @param string $sql
     * @param array $params (default: [])
     * @return Statement
     */
    public function query(string $sql, $params = []) : Statement
    {
        return $this->_query($sql, null, $params);
    }

    /**
     * Executes an SQL(INSERT/UPDATE/DELETE) statement, returning affected rows count.
     *
     * @param string $sql
     * @param array $params (default: [])
     * @return int
     */
    public function execute(string $sql, $params = []) : int
    {
        return $this->query($sql, $params)->affectedRows();
    }

    /**
     * Execute given SQL and get the result (N rows and M columns).
     *
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param string $class (default: 'stdClass')
     * @return ResultSet of given class instance
     */
    public function select(string $sql, $order_by = null, array $params = [], string $class = 'stdClass') : ResultSet
    {
        return $this->_query($sql, $order_by, $params)->all($class);
    }

    /**
     * Execute given SQL and get the result as paginator (N rows and M columns).
     *
     * @param string $sql
     * @param OrderBy|array $order_by
     * @param Pager $pager
     * @param array $params (default: [])
     * @param string $class (default: 'stdClass')
     * @param string $count_optimised_sql only have one count total column (default: null)
     * @return Paginator
     */
    public function paginate(string $sql, $order_by, Pager $pager, array $params = [], string $class = 'stdClass', ?string $count_optimised_sql = null) : Paginator
    {
        $cursor   = $pager->useCursor() ? Cursor::load($pager->cursor()) : null ;
        $total    = $pager->needTotal() ? ($count_optimised_sql ? $this->get(0, $count_optimised_sql, $params) : $this->count($sql, $params)) : null ;
        $order_by = OrderBy::valueOf($order_by);
        return $this->compiler()->paging($this, $total === 0 ? [] : $this->_query($sql, $order_by, $params, $pager, $cursor), $order_by, $pager, $cursor, $total, $class);
    }

    /**
     * Execute given SQL and get the first result (1 rows and M columns).
     *
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param string $class (default: 'stdClass')
     * @return mixed of given class instance
     */
    public function find(string $sql, $order_by = null, array $params = [], string $class = 'stdClass')
    {
        return $this->_query($sql, $order_by, $params)->first($class);
    }

    /**
     * Execute given SQL and extract the result of given column values (N rows and 1 columns).
     *
     * @param string|int $column
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param string|null $type name of convert to type (default: null)
     * @return ResultSet
     */
    public function extract($column, string $sql, $order_by = null, array $params = [], ?string $type = null) : ResultSet
    {
        return $this->_query($sql, $order_by, $params)->allOf($column, $type);
    }

    /**
     * Execute given SQL and extract the result of given column values (1 rows and 1 columns).
     *
     * @param string|int $column
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: null)
     * @param string|null $type name of convert to type (default: null)
     * @return mixed or given type
     */
    public function get($column, string $sql, $order_by = null, array $params = [], ?string $type = null)
    {
        return $this->_query($sql, $order_by, $params)->firstOf($column, $type);
    }

    /**
     * It checks the given SQL result is exists.
     *
     * @param string $sql
     * @param array $params (default: null)
     * @return boolean
     */
    public function exists(string $sql, array $params = []) : bool
    {
        return $this->query("{$sql} LIMIT 1", $params)->first() !== null;
    }

    /**
     * Gets the number of search results for the given SQL.
     *
     * @param string $sql
     * @param array $params (default: null)
     * @return int
     */
    public function count(string $sql, array $params = []) : int
    {
        return $this->get('count', "SELECT count(*) AS count FROM ({$sql}) AS T", null, $params, 'int');
    }

    /**
     * Execute given SQL and applies the callback function to each row of the result.
     *
     * @param callable $callback function(Class $row) : bool {}
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @return void
     */
    public function each(callable $callback, string $sql, $order_by = null, array $params = []) : void
    {
        $this->_query($sql, $order_by, $params)->each($callback);
    }

    /**
     * Execute given SQL then filter the result set using the given callback.
     *
     * @param callable $callback function(Class $row) : bool {}
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @return ResultSet
     */
    public function filter(callable $callback, string $sql, $order_by = null, array $params = []) : ResultSet
    {
        return $this->_query($sql, $order_by, $params)->filter($callback);
    }

    /**
     * Execute given SQL then run a map over each of the result set items.
     *
     * @param callable $callback function(Class $row) : mixed {}
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @return ResultSet
     */
    public function map(callable $callback, string $sql, $order_by = null, array $params = []) : ResultSet
    {
        return $this->_query($sql, $order_by, $params)->map($callback);
    }

    /**
     * Execute given SQL then reduce the result set to a single value.
     *
     * @param callable $reducer function(Class $row, $carry) : mixed {}
     * @param mixed $initial
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @return mixed
     */
    public function reduce(callable $reducer, $initial, string $sql, $order_by = null, array $params = [])
    {
        return $this->_query($sql, $order_by, $params)->reduce($reducer, $initial);
    }

    /**
     * Create (Insert) given entity data.
     *
     * @param Entity $entity
     * @param DateTime|null $now (default: null for DateTime::now())
     * @return bool
     */
    public function create(Entity &$entity, ?DateTime $now = null) : bool
    {
        Event::dispatch(new Creating($this, $entity));

        $result = DateTime::freeze(function () use (&$entity) {
            if ($entity::CREATED_AT && ($entity->{$entity::CREATED_AT} ?? true)) {
                $entity->{$entity::CREATED_AT} = DateTime::now();
            }

            $primarys = $entity::primaryKeys();
            $defaults = $entity::defaults();
            $unmaps   = $entity::unmaps();
            $columns  = [];
            $values   = [];
            foreach ($entity as $column => $value) {
                if (in_array($column, $unmaps)) {
                    continue;
                }
                if ($value === null) {
                    if (in_array($column, $primarys)) {
                        continue;
                    }
                    if ($default = $defaults[$column] ?? null) {
                        $entity->$column = $value = Reflector::convert(...$default);
                    }
                }
                $columns[] = $column;
                $values[]  = $value;
            }

            $table_name    = $entity::tabelName();
            $affected_rows = $this->execute("INSERT INTO {$table_name} (".join(',', $columns).") VALUES (:values)", ['values'=> $values]);
            if ($affected_rows !== 1) {
                return false;
            }

            $pk = count($primarys) === 1 ? $primarys[0] : null ;
            if ($pk !== null && !isset($entity->$pk)) {
                $entity->$pk = $this->lastInsertId();
            }
            $entity->origin(clone $entity->removeOrigin());
            return true;
        }, $now);

        if ($result) {
            Event::dispatch(new Created($this, $entity));
        }

        return $result;
    }

    /**
     * Build primary where condition and parameters.
     *
     * @param Entity $entity
     * @return array [string $where, array $params]
     */
    protected function buildPrimaryWheresFrom(Entity $entity) : array
    {
        $class    = get_class($entity);
        $primarys = $class::primaryKeys();
        if (empty($primarys)) {
            throw DatabaseException::by("Can not build SQL because of {$class} entity do not have any primary keys.");
        }

        $where  = [];
        $params = [];
        foreach ($primarys as $column) {
            $where[]               = "{$column} = :c_{$column}";
            $params["c_{$column}"] = $entity->origin() ? $entity->origin()->$column : $entity->$column ;
        }

        return [join(' AND ', $where), $params];
    }

    /**
     * Update given entity changed data.
     *
     * @param Entity $entity
     * @param DateTime|null $now (default: null for DateTime::now())
     * @return bool
     */
    public function update(Entity &$entity, ?DateTime $now = null) : bool
    {
        $old = $entity->origin();
        $now = $now ?? DateTime::now();
        Event::dispatch(new Updating($this, $old, $entity));

        [$where, $params] = $this->buildPrimaryWheresFrom($entity);
        $changes          = $entity->changes();
        if (empty($changes)) {
            return true;
        }

        $sets = [];
        foreach ($changes as $column => $value) {
            $sets[]          = "{$column} = :{$column}";
            $params[$column] = $value;
        }

        if ($entity::UPDATED_AT && !isset($params[$entity::UPDATED_AT])) {
            $sets[]                        = $entity::UPDATED_AT.' = :'.$entity::UPDATED_AT;
            $params[$entity::UPDATED_AT]   = $now;
            $entity->{$entity::UPDATED_AT} = $now;
        }

        $affected_rows = $this->execute("UPDATE ".$entity->tabelName()." SET ".join(', ', $sets)." WHERE {$where}", $params);
        if ($affected_rows !== 1) {
            return false;
        }

        $entity->origin(clone $entity->removeOrigin());
        Event::dispatch(new Updated($this, $old, $entity));
        return true;
    }

    /**
     * Save given entity changed data.
     *
     * @param Entity $entity
     * @param DateTime|null $now (default: null for DateTime::now())
     * @return boolean
     */
    public function save(Entity $entity, ?DateTime $now = null) : bool
    {
        return $entity->exists($this->name) ? $this->update($entity, $now) : $this->create($entity, $now) ;
    }

    /**
     * Delete given entity changed data.
     *
     * @param Entity $entity
     * @return bool
     */
    public function delete(Entity $entity) : bool
    {
        Event::dispatch(new Deleting($this, $entity));
        [$where, $params] = $this->buildPrimaryWheresFrom($entity);

        $affected_rows = $this->execute("DELETE FROM ".$entity->tabelName()." WHERE {$where}", $params);
        if ($affected_rows !== 1) {
            return false;
        }

        Event::dispatch(new Deleted($this, $entity));
        return true;
    }
}

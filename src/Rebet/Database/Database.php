<?php
namespace Rebet\Database;

use Rebet\Tools\Arrays;
use Rebet\Tools\Reflector;
use Rebet\Tools\Config\Configurable;
use Rebet\Database\Analysis\Analyzer;
use Rebet\Database\Analysis\BuiltinAnalyzer;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Compiler\Compiler;
use Rebet\Database\Converter\BuiltinConverter;
use Rebet\Database\Converter\Converter;
use Rebet\Database\DataModel\Entity;
use Rebet\Database\Event\BatchDeleted;
use Rebet\Database\Event\BatchDeleting;
use Rebet\Database\Event\BatchUpdated;
use Rebet\Database\Event\BatchUpdating;
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
use Rebet\Database\Ransack\BuiltinRansacker;
use Rebet\Database\Ransack\Ransacker;
use Rebet\Tools\DateTime\DateTime;
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
            'analyzer'    => BuiltinAnalyzer::class,
            'ransacker'   => BuiltinRansacker::class,
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
     * The PDO instance.
     *
     * @var \PDO
     */
    protected $pdo = null;

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
     * Ransacker of this database.
     *
     * @var Ransacker
     */
    protected $ransacker = null;

    /**
     * Create database instance using given PDO instance.
     *
     * @param string $name of this database (alias ​​for classification)
     * @param \PDO $pdo
     * @param bool $debug (default: false)
     * @param bool $emulated_sql_log (default: true)
     * @param callable|null $log_handler function(string $name, string $sql, array $params = []) (default: depend on configure)
     */
    public function __construct(string $name, \PDO $pdo, bool $debug = false, bool $emulated_sql_log = true, ?callable $log_handler = null)
    {
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        $this->name             = $name;
        $this->pdo              = $pdo;
        $this->debug            = $debug;
        $this->emulated_sql_log = $emulated_sql_log;
        $this->log_handler      = $log_handler ? \Closure::fromCallable($log_handler) : static::config('log_handler') ;
        $this->converter        = static::config('converter')::of($this);
        $this->compiler         = static::config('compiler')::of($this);
        $this->ransacker        = static::config('ransacker')::of($this);
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
        return $this->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Get the server version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_SERVER_VERSION
     *
     * @return string
     */
    public function serverVersion() : string
    {
        return $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get the client version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_CLIENT_VERSION
     *
     * @return string
     */
    public function clientVersion() : string
    {
        return $this->pdo()->getAttribute(\PDO::ATTR_CLIENT_VERSION);
    }

    /**
     * Get the PDO instance.
     *
     * @return \PDO
     */
    public function pdo() : \PDO
    {
        if ($this->closed()) {
            throw new DatabaseException("Database [{$this->name}] connection was lost.");
        }
        return $this->pdo;
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
     * Get SQL analyzer of this database.
     *
     * @param string $sql
     * @return Analyzer
     */
    public function analyzer(string $sql) : Analyzer
    {
        return static::config('analyzer')::of($this, $sql);
    }

    /**
     * Get Ransacker of this database.
     *
     * @param string $sql
     * @return Ransacker
     */
    public function ransacker() : Ransacker
    {
        return $this->ransacker;
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
        $param = $value instanceof PdoParameter ? $value : $this->converter->toPdoType($value);
        if ($param->value === null) {
            return 'NULL';
        }
        return $this->pdo()->quote($param->value, $param->type);
    }

    /**
     * Convert given PHP type value to PDO data type using converter.
     *
     * @param mixed $value
     * @return PdoParameter
     */
    public function convertToPdo($value) : PdoParameter
    {
        return $this->converter->toPdoType($value);
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
        return $this->converter->toPhpType($value, $meta, $type);
    }

    /**
     * Start the transaction.
     *
     * @return self
     * @throws DatabaseException|\PDOException
     */
    public function begin() : self
    {
        if (!$this->pdo()->beginTransaction()) {
            throw $this->exception($this->pdo()->errorInfo());
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
        $this->pdo()->exec($sql);
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
            if (!($sql ? $this->pdo()->exec($sql) : $this->pdo()->rollBack())) {
                throw $this->exception($this->pdo()->errorInfo(), $sql);
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
        if (!$this->pdo()->commit()) {
            throw $this->exception($this->pdo()->errorInfo());
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
        return $this->pdo()->lastInsertId($name);
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
            $stmt = $this->pdo()->prepare($sql, [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // \PDO::ATTR_CURSOR             => \PDO::CURSOR_SCROLL,
            ]);
            if (!$stmt) {
                throw $this->exception($this->pdo()->errorInfo(), $sql);
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
     * @param mixed $params can be arrayable (default: [])
     * @param int|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @param Pager|null $pager (default: null)
     * @param Cursor|null $cursor (default: null)
     * @return Statement
     */
    protected function _query(string $sql, $order_by = null, $params = [], ?int $limit = null, bool $for_update = false, ?Pager $pager = null, ?Cursor $cursor = null) : Statement
    {
        [$sql, $params] = $this->compiler->compile($sql, OrderBy::valueOf($order_by), $params, $pager, $cursor);
        $limit          = $limit && $pager === null ? " LIMIT {$limit}" : "" ;
        $for_update     = $for_update ? " FOR UPDATE" : "" ;
        return $this->prepare("{$sql}{$limit}{$for_update}")->execute(Arrays::toArray($params));
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
     * @param int|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @param string $class (default: 'stdClass')
     * @return ResultSet of given class instance
     */
    public function select(string $sql, $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false, string $class = 'stdClass') : ResultSet
    {
        return $this->_query($sql, $order_by, $params, $limit, $for_update)->all($class);
    }

    /**
     * Execute given SQL and get the result as paginator (N rows and M columns).
     *
     * @param string $sql
     * @param OrderBy|array $order_by
     * @param Pager $pager
     * @param array $params (default: [])
     * @param bool $for_update (default: false)
     * @param string $class (default: 'stdClass')
     * @param string $optimised_count_sql only have one count total column (default: null)
     * @return Paginator
     */
    public function paginate(string $sql, $order_by, Pager $pager, array $params = [], bool $for_update = false, string $class = 'stdClass', ?string $optimised_count_sql = null) : Paginator
    {
        $cursor   = $pager->useCursor() ? Cursor::load($pager->cursor()) : null ;
        $total    = $pager->needTotal() ? ($optimised_count_sql ? $this->get(0, $optimised_count_sql, $params) : $this->count($sql, $params)) : null ;
        $order_by = OrderBy::valueOf($order_by);
        return $this->compiler->paging($total === 0 ? [] : $this->_query($sql, $order_by, $params, null, $for_update, $pager, $cursor), $order_by, $pager, $cursor, $total, $class);
    }

    /**
     * Execute given SQL and get the first result (1 rows and M columns).
     *
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param bool $for_update (default: false)
     * @param string $class (default: 'stdClass')
     * @return mixed of given class instance
     */
    public function find(string $sql, $order_by = null, array $params = [], bool $for_update = false, string $class = 'stdClass')
    {
        return $this->_query($sql, $order_by, $params, 1, $for_update)->first($class);
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
        return $this->_query($sql, $order_by, $params, 1)->firstOf($column, $type);
    }

    /**
     * It checks the given SQL result is exist.
     *
     * @param string $sql
     * @param array $params (default: null)
     * @return boolean
     */
    public function exist(string $sql, array $params = []) : bool
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
        return $this->get('count', "SELECT COUNT(*) AS count FROM ({$sql}) AS T", null, $params, 'int');
    }

    /**
     * Execute given SQL and applies the callback function to each row of the result.
     *
     * @param callable $callback function(Class $row) : bool {}
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param int|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @return void
     */
    public function each(callable $callback, string $sql, $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false) : void
    {
        $this->_query($sql, $order_by, $params, $limit, $for_update)->each($callback);
    }

    /**
     * Execute given SQL then filter the result set using the given callback.
     *
     * @param callable $callback function(Class $row) : bool {}
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param int|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @return ResultSet
     */
    public function filter(callable $callback, string $sql, $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false) : ResultSet
    {
        return $this->_query($sql, $order_by, $params, $limit, $for_update)->filter($callback);
    }

    /**
     * Execute given SQL then run a map over each of the result set items.
     *
     * @param callable $callback function(Class $row) : mixed {}
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param int|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @return ResultSet
     */
    public function map(callable $callback, string $sql, $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false) : ResultSet
    {
        return $this->_query($sql, $order_by, $params, $limit, $for_update)->map($callback);
    }

    /**
     * Execute given SQL then reduce the result set to a single value.
     *
     * @param callable $reducer function(Class $row, $carry) : mixed {}
     * @param mixed $initial
     * @param string $sql
     * @param OrderBy|array|null $order_by (default: null)
     * @param array $params (default: [])
     * @param int|null $limit (default: null)
     * @return mixed
     */
    public function reduce(callable $reducer, $initial, string $sql, $order_by = null, array $params = [], ?int $limit = null)
    {
        return $this->_query($sql, $order_by, $params, $limit)->reduce($reducer, $initial);
    }

    /**
     * Create (Insert) given entity data.
     * This method ignore unmaps (non public and @Unmaps annotated) properties and dynamic properties.
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
                if (in_array($column, $unmaps) || $entity->isDynamicProperty($column)) {
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
     * Append where condition to given SQL.
     *
     * @param string $sql
     * @param array $where
     * @return string
     */
    public function appendWhereTo(string $sql, array $where) : string
    {
        if (empty($where)) {
            return $sql;
        }
        $analyzer = $this->analyzer($sql);
        $where    = implode(' AND ', $where);
        if ($analyzer->hasGroupBy()) {
            return $analyzer->hasHaving() ? "{$sql} AND ({$where})" : "{$sql} HAVING {$where}" ;
        }
        return $analyzer->hasWhere() || $analyzer->hasHaving() ? "{$sql} AND ({$where})" : "{$sql} WHERE {$where}" ;
    }

    /**
     * Build primary where condition and parameters.
     *
     * @param Entity $entity
     * @return Condition
     */
    public static function buildPrimaryWheresFrom(Entity $entity) : Condition
    {
        $class    = get_class($entity);
        $primarys = $class::primaryKeys();
        if (empty($primarys)) {
            throw new DatabaseException("Can not build SQL because of {$class} entity do not have any primary keys.");
        }

        $where  = [];
        $params = [];
        foreach ($primarys as $column) {
            $where[]         = "{$column} = :{$column}";
            $params[$column] = $entity->origin() ? $entity->origin()->$column : $entity->$column ;
        }

        return new Condition(join(' AND ', $where), $params);
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

        $condition = static::buildPrimaryWheresFrom($entity);
        $params    = $condition->params;
        $changes   = $entity->changes();
        $sets      = [];
        foreach ($changes as $column => $value) {
            $key          = "v_{$column}";
            $sets[]       = "{$column} = :{$key}";
            $params[$key] = $value;
        }

        if ($entity::UPDATED_AT && !isset($params[$entity::UPDATED_AT])) {
            $sets[]                        = $entity::UPDATED_AT.' = :'.$entity::UPDATED_AT;
            $params[$entity::UPDATED_AT]   = $now;
            $entity->{$entity::UPDATED_AT} = $now;
        } elseif (empty($changes)) {
            return true;
        }

        $affected_rows = $this->execute("UPDATE ".$entity::tabelName()." SET ".join(', ', $sets).$condition->where(), $params);
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
        return $entity->exist($this->name) ? $this->update($entity, $now) : $this->create($entity, $now) ;
    }

    /**
     * Delete given entity data.
     *
     * @param Entity $entity
     * @return bool
     */
    public function delete(Entity $entity) : bool
    {
        Event::dispatch(new Deleting($this, $entity));
        $condition     = static::buildPrimaryWheresFrom($entity);
        $affected_rows = $this->execute("DELETE FROM ".$entity->tabelName().$condition->where(), $condition->params);
        if ($affected_rows !== 1) {
            return false;
        }

        Event::dispatch(new Deleted($this, $entity));
        return true;
    }

    /**
     * Update data using ransack conditions.
     *
     * @param string $entity class name
     * @param array $changes
     * @param mixed $ransack conditions that arrayable
     * @param array $alias (default: [])
     * @param DateTime|null $now (default: null)
     * @return int affected row count
     */
    public function updates(string $entity, array $changes, $ransack, array $alias = [], ?DateTime $now = null) : int
    {
        $now = $now ?? DateTime::now();
        Event::dispatch(new BatchUpdating($this, $entity, $changes, $ransack, $now));
        if ($entity::UPDATED_AT) {
            $changes[$entity::UPDATED_AT] = $changes[$entity::UPDATED_AT] ?? $now ;
        }

        $sets      = [];
        $condition = $this->ransacker->build($ransack, $alias);
        $params    = $condition->params;
        foreach ($changes as $column => $value) {
            $key          = "v_{$column}";
            $sets[]       = "{$column} = :{$key}";
            $params[$key] = $value;
        }

        $affected_rows = $this->execute("UPDATE ".$entity::tabelName()." SET ".join(', ', $sets).$condition->where(), $params);
        if ($affected_rows !== 0) {
            Event::dispatch(new BatchUpdated($this, $entity, $changes, $ransack, $now, $affected_rows));
        }
        return $affected_rows;
    }

    /**
     * Delete data using ransack conditions.
     *
     * @param string $entity class name
     * @param mixed $ransack conditions that arrayable
     * @param array $alias (default: [])
     * @return int affected row count
     */
    public function deletes(string $entity, $ransack, array $alias = []) : int
    {
        Event::dispatch(new BatchDeleting($this, $entity, $ransack));

        $condition     = $this->ransacker->build($ransack, $alias);
        $affected_rows = $this->execute("DELETE FROM ".$entity::tabelName().$condition->where(), $condition->params);
        if ($affected_rows !== 0) {
            Event::dispatch(new BatchDeleted($this, $entity, $ransack, $affected_rows));
        }
        return $affected_rows;
    }

    /**
     * It checks the data is exists using ransack conditions.
     *
     * @param string $entity class name
     * @param mixed $ransack conditions that arrayable
     * @param array $alias (default: [])
     * @return bool
     */
    public function exists(string $entity, $ransack, array $alias = []) : bool
    {
        $condition = $this->ransacker->build($ransack, $alias);
        return $this->exist("SELECT * FROM ".$entity::tabelName().$condition->where(), $condition->params);
    }

    /**
     * Count data using ransack conditions.
     *
     * @param string $entity class name
     * @param mixed $ransack conditions that arrayable
     * @param array $alias (default: [])
     * @return int
     */
    public function counts(string $entity, $ransack, array $alias = []) : int
    {
        $condition = $this->ransacker->build($ransack, $alias);
        return $this->get('count', "SELECT COUNT(*) AS count FROM ".$entity::tabelName().$condition->where(), [], $condition->params);
    }

    /**
     * Close database connection.
     *
     * @return void
     */
    public function close() : void
    {
        if ($this->pdo) {
            $this->rollback();
            $this->pdo = null;
        }
    }

    /**
     * It checks the database connection is closed or not.
     *
     * @return boolean
     */
    public function closed() : bool
    {
        return $this->pdo === null;
    }
}

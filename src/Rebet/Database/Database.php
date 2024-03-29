<?php
namespace Rebet\Database;

use Rebet\Database\Analysis\Analyzer;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Compiler\Compiler;
use Rebet\Database\DataModel\Entity;
use Rebet\Database\Driver\Driver;
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
use Rebet\Event\Event;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;

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

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/database.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'compiler'    => BuiltinCompiler::class,
            'ransacker'   => BuiltinRansacker::class,
            'log_handler' => null, // function(Database $db, Query $query) {}
        ];
    }

    /**
     * The name of this database (alias ​​for classification).
     *
     * @var string
     */
    protected $name = null;

    /**
     * The PDO Driver instance.
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
     * SQL logging handler for debug
     *
     * @var \Closure function(Database $db, Query $query) {}
     */
    protected $log_handler = null;

    /**
     * Compiler of SQL template and params.
     *
     * @var Compiler
     */
    protected $compiler = null;

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
     * @param Driver $driver
     * @param bool $debug (default: false)
     * @param callable|null $log_handler function(Database $db, Query $query) (default: depend on configure)
     */
    public function __construct(string $name, Driver $driver, bool $debug = false, ?callable $log_handler = null)
    {
        $this->name        = $name;
        $this->driver      = $driver;
        $this->debug       = $debug;
        $this->log_handler = $log_handler ? \Closure::fromCallable($log_handler) : static::config('log_handler') ;
        $this->compiler    = static::config('compiler')::of($driver);
        $this->ransacker   = static::config('ransacker')::of($driver);
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
     * Get the PDO Driver of this database.
     *
     * @return Driver
     */
    public function driver() : Driver
    {
        return $this->driver;
    }

    /**
     * Get the PDO driver name of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_DRIVER_NAME
     *
     * @return string
     */
    public function driverName() : string
    {
        return $this->driver->name();
    }

    /**
     * Get the server version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_SERVER_VERSION
     *
     * @return string
     */
    public function serverVersion() : string
    {
        return $this->driver->serverVersion();
    }

    /**
     * Get the client version of this database.
     * NOTE: This method return PDO drivers attribute of PDO::ATTR_CLIENT_VERSION, if the driver returned array then return combineded string `$key=$value;...`.
     *
     * @return string
     */
    public function clientVersion() : string
    {
        return $this->driver->clientVersion();
    }

    /**
     * Get the PDO instance.
     *
     * @return \PDO
     */
    public function pdo() : \PDO
    {
        return $this->driver->pdo();
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
     * Get SQL analyzer of this database.
     *
     * @param string $sql
     * @return Analyzer
     */
    public function analyzer(string $sql) : Analyzer
    {
        return $this->driver->analyzer($sql);
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
     * @return self
     */
    public function debug(bool $debug = true) : self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * It checks that debug log output is active or not.
     *
     * @return bool
     */
    public function isDebug() : bool
    {
        return $this->debug;
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
            call_user_func($this->log_handler, $this, $this->sql($sql, $params));
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
        return DatabaseException::from('db:'.$this->name(), $error, $sql, $params)->db($this);
    }

    /**
     * Start the transaction.
     *
     * @return self
     * @throws DatabaseException
     */
    public function begin() : self
    {
        $this->log($this->driver->begin());
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
        $this->log($this->driver->savepoint($name));
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
        $this->log($this->driver->rollback($savepoint, $quiet) ?? "-- Rollback failed, but continue processing by quiet mode.");
        return $this;
    }

    /**
     * Commit the transaction.
     *
     * @return self
     * @throws DatabaseException
     */
    public function commit() : self
    {
        $this->log($this->driver->commit());
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
     * Truncate given table data.
     * NOTE: This method reset identity number
     *
     * @param string $table_name
     * @param bool $with_vacuum if needed for sqlite (default: true)
     * @return void
     */
    public function truncate(string $table_name, ?bool $with_vacuum = true) : void
    {
        $this->log($this->driver->truncate($table_name, $with_vacuum));
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
        return new Statement($this, $this->driver->prepare($sql));
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
        $query = $this->compiler->compile($sql, OrderBy::valueOf($order_by), $params, $pager, $cursor);
        $query = $limit && $pager === null ? $query->appendLimitOffset($limit) : $query ;
        $query = $for_update ? $query->appendForUpdate() : $query ;
        return $this->prepare($query->sql())->execute($query->params());
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
    public function exists(string $sql, array $params = []) : bool
    {
        return $this->query($this->driver->appendLimitOffset($sql, 1), $params)->first() !== null;
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
     * @uses Event::dispatch Creating when before data create.
     * @uses Event::dispatch Created when after data created.
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
                $columns[] = $this->driver->quoteIdentifier($column);
                $values[]  = $value;
            }

            $table_name    = $this->driver->quoteIdentifier($entity::tabelName());
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
     * Update given entity changed data.
     *
     * @param Entity $entity
     * @param DateTime|null $now (default: null for DateTime::now())
     * @return bool
     * @uses Event::dispatch Updating when before data update.
     * @uses Event::dispatch Updated when after data updated.
     */
    public function update(Entity &$entity, ?DateTime $now = null) : bool
    {
        $old = $entity->origin();
        $now = $now ?? DateTime::now();
        Event::dispatch(new Updating($this, $old, $entity));

        $condition = $entity->buildPrimaryWhere($this);
        $params    = $condition->params();
        $changes   = $entity->changes();
        $sets      = [];
        foreach ($changes as $column => $value) {
            $key          = "v_{$column}";
            $sets[]       = "{$this->driver->quoteIdentifier($column)} = :{$key}";
            $params[$key] = $value;
        }

        if ($entity::UPDATED_AT && !isset($params[$entity::UPDATED_AT])) {
            $sets[]                        = $this->driver->quoteIdentifier($entity::UPDATED_AT).' = :'.$entity::UPDATED_AT;
            $params[$entity::UPDATED_AT]   = $now;
            $entity->{$entity::UPDATED_AT} = $now;
        } elseif (empty($changes)) {
            return true;
        }

        $affected_rows = $this->execute("UPDATE ".$this->driver->quoteIdentifier($entity::tabelName())." SET ".join(', ', $sets).$condition->asWhere(), $params);
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
     * Delete given entity data.
     *
     * @param Entity $entity
     * @return bool
     * @uses Event::dispatch Deleting when before data delete.
     * @uses Event::dispatch Deleted when after data delete.
     */
    public function delete(Entity $entity) : bool
    {
        Event::dispatch(new Deleting($this, $entity));
        $condition     = $entity->buildPrimaryWhere($this);
        $affected_rows = $this->execute("DELETE FROM ".$this->driver->quoteIdentifier($entity->tabelName()).$condition->asWhere(), $condition->params());
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
     * @uses Event::dispatch BatchUpdating when before batch update.
     * @uses Event::dispatch BatchUpdated when after batch update.
     */
    public function updateBy(string $entity, array $changes, $ransack, array $alias = [], ?DateTime $now = null) : int
    {
        $now = $now ?? DateTime::now();
        Event::dispatch(new BatchUpdating($this, $entity, $changes, $ransack, $now));
        if ($entity::UPDATED_AT) {
            $changes[$entity::UPDATED_AT] = $changes[$entity::UPDATED_AT] ?? $now ;
        }

        $sets      = [];
        $condition = $this->ransacker->build($ransack, $alias);
        $params    = $condition->params();
        foreach ($changes as $column => $value) {
            $key          = "v_{$column}";
            $sets[]       = "{$this->driver->quoteIdentifier($column)} = :{$key}";
            $params[$key] = $value;
        }

        $affected_rows = $this->execute("UPDATE ".$this->driver->quoteIdentifier($entity::tabelName())." SET ".join(', ', $sets).$condition->asWhere(), $params);
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
     * @uses Event::dispatch BatchDeleting when before batch delete.
     * @uses Event::dispatch BatchDeleted when after batch delete.
     */
    public function deleteBy(string $entity, $ransack, array $alias = []) : int
    {
        Event::dispatch(new BatchDeleting($this, $entity, $ransack));

        $condition     = $this->ransacker->build($ransack, $alias);
        $affected_rows = $this->execute("DELETE FROM ".$this->driver->quoteIdentifier($entity::tabelName()).$condition->asWhere(), $condition->params());
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
    public function existsBy(string $entity, $ransack, array $alias = []) : bool
    {
        $condition = $this->ransacker->build($ransack, $alias);
        return $this->exists("SELECT * FROM ".$this->driver->quoteIdentifier($entity::tabelName()).$condition->asWhere(), $condition->params());
    }

    /**
     * Count data using ransack conditions.
     *
     * @param string $entity class name
     * @param mixed $ransack conditions that arrayable
     * @param array $alias (default: [])
     * @return int
     */
    public function countBy(string $entity, $ransack, array $alias = []) : int
    {
        $condition = $this->ransacker->build($ransack, $alias);
        return $this->get('count', "SELECT COUNT(*) AS count FROM ".$this->driver->quoteIdentifier($entity::tabelName()).$condition->asWhere(), [], $condition->params());
    }

    /**
     * Close database connection.
     *
     * @return void
     */
    public function close() : void
    {
        if (!$this->closed()) {
            $this->driver->close();
        }
    }

    /**
     * It checks the database connection is closed or not.
     *
     * @return boolean
     */
    public function closed() : bool
    {
        return $this->driver->closed();
    }

    /**
     * Create SQL query for this database driver.
     *
     * @param string $sql
     * @param array $params (default: [])
     * @return Query
     */
    public function sql(string $sql, array $params = []) : Query
    {
        return $this->driver->sql($sql, $params);
    }
}

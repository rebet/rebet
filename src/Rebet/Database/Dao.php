<?php
namespace Rebet\Database;

use Rebet\Database\Driver\MysqlDriver;
use Rebet\Database\Driver\PgsqlDriver;
use Rebet\Database\Driver\SqliteDriver;
use Rebet\Database\Driver\SqlsrvDriver;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Utility\Strings;

/**
 * Dao Class
 *
 * Database Access Object based on PDO.
 * The PDO used for DAO can be specified by the following definition.
 *
 *     Dao::class => [
 *         'dbs' => [
 *              'name' => [                       // Alias ​​for classification, Not a schema name
 *                  'dsn'      => 'dsn:string',   // DSN string or function returned PDO object `function():\PDO { ... }`
 *                  'user'     => 'user',         // Database user name
 *                  'password' => 'password',     // Database pasword
 *                  'options'  => [],             // PDO options
 *              ],
 *         ]
 *     ]
 *
 * Dynamically call the default database method
 * --------------------
 * @method static string            name()                                                                                                                                                                      Dynamically call the default database method.
 * @method static string            driverName()                                                                                                                                                                Dynamically call the default database method.
 * @method static string            serverVersion()                                                                                                                                                             Dynamically call the default database method.
 * @method static string            clientVersion()                                                                                                                                                             Dynamically call the default database method.
 * @method static \PDO              pdo()                                                                                                                                                                       Dynamically call the default database method.
 * @method static Compiler          compiler()                                                                                                                                                                  Dynamically call the default database method.
 * @method static Driver            driver()                                                                                                                                                                    Dynamically call the default database method.
 * @method static Analyzer          analyzer(string $sql)                                                                                                                                                       Dynamically call the default database method.
 * @method static Ransacker         ransacker()                                                                                                                                                                 Dynamically call the default database method.
 * @method static Database          debug(bool $debug = true)                                                                                                                                                   Dynamically call the default database method.
 * @method static bool              isDebug()                                                                                                                                                                   Dynamically call the default database method.
 * @method static void              log(string $sql, array $params = [])                                                                                                                                        Dynamically call the default database method.
 * @method static DatabaseException exception(array|\PDOException $error, ?string $sql = null, array $params = [])                                                                                              Dynamically call the default database method.
 * @method static Database          begin()                                                                                                                                                                     Dynamically call the default database method.
 * @method static Database          savepoint(string $name)                                                                                                                                                     Dynamically call the default database method.
 * @method static Database          rollback(?string $savepoint = null, bool $quiet = true)                                                                                                                     Dynamically call the default database method.
 * @method static Database          commit()                                                                                                                                                                    Dynamically call the default database method.
 * @method static Database          transaction(\Closure $callback)                                                                                                                                             Dynamically call the default database method.
 * @method static string            lastInsertId(?string $name = null)                                                                                                                                          Dynamically call the default database method.
 * @method static Statement         query(string $sql, $params = [])                                                                                                                                            Dynamically call the default database method.
 * @method static int               execute(string $sql, $params = [])                                                                                                                                          Dynamically call the default database method.
 * @method static ResultSet         select(string $sql, OrderBy|array|null $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false, string $class = 'stdClass')                      Dynamically call the default database method.
 * @method static Paginator         paginate(string $sql, OrderBy|array $order_by, Pager $pager, array $params = [], bool $for_update = false, string $class = 'stdClass', ?string $optimised_count_sql = null) Dynamically call the default database method.
 * @method static mixed             find(string $sql, OrderBy|array|null $order_by = null, array $params = [], bool $for_update = false, string $class = 'stdClass')                                            Dynamically call the default database method.
 * @method static ResultSet         extract(string|int $column, string $sql, OrderBy|array|null $order_by = null, array $params = [], ?string $type = null)                                                     Dynamically call the default database method.
 * @method static mixed             get(string|int $column, string $sql, OrderBy|array|null $order_by = null, array $params = [], ?string $type = null)                                                         Dynamically call the default database method.
 * @method static bool              exist(string $sql, array $params = [])                                                                                                                                      Dynamically call the default database method.
 * @method static int               count(string $sql, array $params = [])                                                                                                                                      Dynamically call the default database method.
 * @method static void              each(callable $callback, string $sql, OrderBy|array|null $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false)                                Dynamically call the default database method.
 * @method static ResultSet         filter(callable $callback, string $sql, OrderBy|array|null $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false)                              Dynamically call the default database method.
 * @method static ResultSet         map(callable $callback, string $sql, OrderBy|array|null $order_by = null, array $params = [], ?int $limit = null, bool $for_update = false)                                 Dynamically call the default database method.
 * @method static mixed             reduce(callable $reducer, $initial, string $sql, OrderBy|array|null $order_by = null, array $params = [], ?int $limit = null)                                               Dynamically call the default database method.
 * @method static bool              create(Entity &$entity, ?DateTime $now = null)                                                                                                                              Dynamically call the default database method.
 * @method static bool              update(Entity &$entity, ?DateTime $now = null)                                                                                                                              Dynamically call the default database method.
 * @method static bool              save(Entity $entity, ?DateTime $now = null)                                                                                                                                 Dynamically call the default database method.
 * @method static bool              delete(Entity $entity)                                                                                                                                                      Dynamically call the default database method.
 * @method static int               updateBy(string $entity, array $changes, $ransack, array $alias = [], ?DateTime $now = null)                                                                                Dynamically call the default database method.
 * @method static int               deleteBy(string $entity, mixed $ransack, array $alias = [])                                                                                                                 Dynamically call the default database method.
 * @method static bool              existsBy(string $entity, mixed $ransack, array $alias = [])                                                                                                                 Dynamically call the default database method.
 * @method static int               counts(string $entity, mixed $ransack, array $alias = [])                                                                                                                   Dynamically call the default database method.
 * @method static void              close()                                                                                                                                                                     Dynamically call the default database method.
 * @method static bool              closed()                                                                                                                                                                    Dynamically call the default database method.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Dao
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/database.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'default_db' => null,
            'dbs'        => [],
            'drivers'    => [
                'sqlite' => SqliteDriver::class,
                'mysql'  => MysqlDriver::class,
                'pgsql'  => PgsqlDriver::class,
                'sqlsrv' => SqlsrvDriver::class,
            ],
        ];
    }

    /**
     * Database dbs
     *
     * @var Database[]
     */
    protected static $dbs = null;

    /**
     * Current selected database.
     *
     * @var Database
     */
    protected static $current = null;

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Crear the Database instance.
     * NOTE: All existing DB connections will be rolled back.
     *
     * @param string|null $name (default: null for all clear)
     * @return void
     */
    public static function clear(?string $name = null) : void
    {
        if ($name === null) {
            foreach (static::$dbs ?? [] as $db) {
                $db->close();
            }
            static::$dbs     = [];
            static::$current = null;
        } elseif (isset(static::$dbs[$name])) {
            static::$dbs[$name]->close();
            unset(static::$dbs[$name]);
            if (static::$current !== null && static::$current->name() === $name) {
                static::$current = null;
            }
        }
    }

    /**
     * Get the Database for given db name.
     *
     * @param string $name when the null given return the default db Database (default: null)
     * @param bool $update_current_db (default: true)
     * @return Database
     */
    public static function db(?string $name = null, bool $update_current_db = true) : Database
    {
        $name = $name ?? static::config('default_db');
        if ($db = static::$dbs[$name] ?? null) {
            return $update_current_db ? static::$current = $db : $db ;
        }

        $dsn    = static::config("dbs.{$name}.dsn");
        $driver = null;
        if (is_callable($dsn)) {
            $driver = call_user_func($dsn);
        } else {
            $driver_name  = static::config("dbs.{$name}.driver", false) ?? Strings::latrim($dsn, ':') ;
            $driver_class = static::config("drivers.{$driver_name}");
            $driver       = $driver_class::create($dsn, static::config("dbs.{$name}.user", false), static::config("dbs.{$name}.password", false), static::config("dbs.{$name}.options", false, []));
        }

        $db = new Database(
            $name,
            $driver,
            static::config("dbs.{$name}.debug", false, false),
            static::config("dbs.{$name}.log_handler", false, null)
        );

        static::$dbs[$name] = $db;
        if ($update_current_db) {
            static::$current = $db;
        }
        return $db;
    }

    /**
     * Get current selected database.
     *
     * @return Database|null
     */
    public static function current() : ?Database
    {
        return static::$current;
    }

    /**
     * Dynamically call the default Database instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return static::db()->$method(...$parameters);
    }
}

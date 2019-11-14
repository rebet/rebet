<?php
namespace Rebet\Database;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\Database\Driver\Driver;
use Rebet\Database\Driver\PdoDriver;

/**
 * Dao Class
 *
 * Database Access Object by specifying any driver for each db definition.
 * The driver MUST be implements PDO Interface.
 *
 * The driver used for Rebet database access object can be specified by the following definition.
 *
 *     Dao::class => [
 *         'dbs' => [
 *              'name' => [                        // Alias ​​for classification, Not a schema name
 *                  'driver'     => Driver::class, // PDO Interface implementation class
 *                  'arg_name_1' => value_1,       // Constructor argument name and value for 'driver' class.
 *                  (snip)                         // If the argument has default value (or variadic), then the parameter can be optional.
 *                  'arg_name_n' => value_n,       // Also, you don't have to worry about the order of parameter definition.
 *              ],
 *         ]
 *     ]
 *
 * If it is difficult to build a driver with simple constructor parameter specification, you can build a driver by specifying a factory method.
 *
 *     Dao::class => [
 *         'dbs' => [
 *              'name' => [
 *                  'driver' => function() { ... Build any log driver here ... } , // Return PDO Interface implementation class
 *              ],
 *         ]
 *     ]
 *
 * Based on this specification, Rebet provides PDO driver class.
 * The drivers prepared in the package are as follows.
 *
 * Drivers
 * --------------------
 * @see \Rebet\Database\Driver\PdoDriver::class (Library Default)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Dao
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'dbs' => [
                'main' => [
                    'driver'           => PdoDriver::class,
                    'dsn'              => null,
                    'user'             => null,
                    'password'         => null,
                    'options'          => [],
                    'debug'            => false,
                    'emulated_sql_log' => true,
                    'log_handler'      => null,
                ],
            ],
            'default_db' => 'main',
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
            foreach (static::$dbs as $db) {
                $db->rollback();
            }
            static::$dbs     = [];
            static::$current = null;
        } else {
            static::$dbs[$name]->rollback();
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
        $db   = static::$dbs[$name] ?? null;
        if ($db !== null) {
            if ($update_current_db) {
                static::$current = $db;
            }
            return $db;
        }

        $conf = static::config("dbs.{$name}", false);
        if ($conf === null) {
            throw LogicException::by("Unable to create '{$name}' db Dao. Undefined configure 'Rebet\Database\Dao.dbs.{$name}'.");
        }
        if (!isset($conf['driver'])) {
            throw LogicException::by("Unable to create '{$name}' db Dao. Driver is undefined.");
        }
        $driver = $conf['driver'];
        $db     = new Database(
            $name,
            is_callable($driver) ? call_user_func($driver, $name) : (is_string($driver) ? Reflector::create($driver, $conf) : $driver),
            $conf['debug'] ?? false,
            $conf['emulated_sql_log'] ?? true,
            $conf['log_handler'] ?? null
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
}

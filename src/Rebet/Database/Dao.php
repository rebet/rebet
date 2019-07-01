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
                    'log_handler'      => null, // function(string $db_name, string $sql, array $params = []) : void
                    'emulated_sql_log' => true,
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
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get the Database for given db name.
     *
     * @param string $name when the null given return the default db Database (default: null)
     * @return Database
     */
    public static function db(?string $name = null) : Database
    {
        $name = $name ?? static::config('default_db');
        $db   = static::$dbs[$name] ?? null;
        if ($db !== null) {
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
            $conf['emulated_sql_log'] ?? true,
            $conf['log_handler'] ?? null
        );

        static::$dbs[$name] = $db;
        return $db;
    }
}

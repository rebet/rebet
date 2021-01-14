<?php
namespace Rebet\Database\Converter;

use Rebet\Database\Database;
use Rebet\Database\PdoParameter;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Enum\Enum;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;

/**
 * Builtin Converter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinConverter implements Converter
{
    /**
     * Database
     *
     * @var Database
     */
    protected $db;

    /**
     * Create Builtin Conpiler of given database.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public static function of(Database $db) : Converter
    {
        return new static($db);
    }

    /**
     * {@inheritDoc}
     */
    public function toPdoType($value) : PdoParameter
    {
        if ($value instanceof Enum) {
            $value = $value->value;
        }

        $driver = $this->db->driverName();
        switch (true) {
            case $value instanceof PdoParameter:       return $value;
            case $value === null:                      return PdoParameter::null();
            case is_bool($value):                      return $driver === 'mysql' ? PdoParameter::int($value ? 1 : 0) : PdoParameter::bool($value);
            case is_int($value):                       return PdoParameter::int($value);
            case is_float($value):                     return PdoParameter::str($value);
            case is_resource($value):                  return PdoParameter::lob(stream_get_contents($value), $driver === 'sqlsrv' ? \PDO::SQLSRV_ENCODING_BINARY : null);
            case $value instanceof Date:               return PdoParameter::str($value->format("Y-m-d"));
            case $value instanceof \DateTimeInterface: return PdoParameter::str($value->format($driver === 'pgsql' ? "Y-m-d H:i:sO" : "Y-m-d H:i:s"));
            case $value instanceof Decimal:            return PdoParameter::str($value->normalize()->format(true, '.', ''));
        }

        return PdoParameter::str($value);
    }

    /**
     * {@inheritDoc}
     *
     * @see 'sqlite' native_type from http://gcov.php.net/PHP_7_1/lcov_html/ext/pdo_sqlite/sqlite_statement.c.gcov.php
     * @see 'mysql'  native_type from http://gcov.php.net/PHP_7_1/lcov_html/ext/pdo_mysql/mysql_statement.c.gcov.php
     * @see 'pgsql'  native_type from http://gcov.php.net/PHP_7_1/lcov_html/ext/pdo_pgsql/pgsql_statement.c.gcov.php and `SELECT TYPNAME FROM PG_TYPE` results.
     * @see 'dblib'  native_type from http://gcov.php.net/PHP_7_1/lcov_html/ext/pdo_dblib/dblib_stmt.c.gcov.php
     * @see 'sqlsrv' native_type from https://documentation.help/MS-Drivers-PHP-SQL-Server/c02f6942-0484-4567-a78e-fe8aa2053536.htm
     */
    public function toPhpType($value, array $meta = [], ?string $type = null)
    {
        if ($value === null) {
            return null;
        }
        if ($type !== null) {
            return Reflector::convert($value, $type);
        }
        $driver = $this->db->driverName();
        switch ($native_type = strtolower($meta[$driver === 'sqlsrv' ? 'sqlsrv:decl_type' : 'native_type'] ?? 'unknown')) {
            case 'null':             // mysql, sqlite
                return null;

            case 'sql_variant':      // sqlsrv, dblib
                return (string)$value;

            case 'string':           // mysql, sqlite
            case 'var_string':       // mysql
            case 'text':             // sqlsrv, pgsql, dblib
            case 'varchar':          // sqlsrv, pgsql, dblib
            case 'bpchar':           // pgsql
            case 'uuid':             // pgsql
            case 'uniqueidentifier': // sqlsrv, dblib
            case 'pg_lsn':           // pgsql
            case 'nvarchar':         // sqlsrv, dblib
            case 'char':             // sqlsrv, dblib
            case 'ntext':            // sqlsrv, dblib
            case 'nchar':            // sqlsrv, dblib
            case 'sql_variant':      // sqlsrv, dblib
                return (string)$value;

            case 'tiny':             // mysql
                return $meta['len'] === 1 ? boolval($value) : intval($value) ;

            case 'short':            // mysql
            case 'long':             // mysql
            case 'int24':            // mysql
            case 'integer':          // sqlite
            case 'int2':             // pgsql
            case 'int4':             // pgsql
            case 'int8':             // pgsql
            case 'varbit':           // pgsql
            case 'tinyint':          // sqlsrv, dblib
            case 'smallint':         // sqlsrv, dblib
            case 'int':              // sqlsrv, dblib
            case 'int identity':     // sqlsrv
                return intval($value) ;

            case 'longlong':         // mysql
            case 'bigint':           // sqlsrv, dblib
                return PHP_INT_SIZE === 8 ? intval($value) : (string)$value ;

            case 'float':            // sqlsrv, mysql, dblib
            case 'double':           // mysql, sqlite
            case 'real':             // sqlsrv, dblib
            case 'float4':           // pgsql
            case 'float8':           // pgsql
                return floatval($value);

            case 'bit':              // sqlsrv, mysql, dblib, pgsql
                if ($driver === 'sqlsrv') {
                    return boolval($value);
                }
                if ($driver === 'pgsql') {
                    return $meta['precision'] < PHP_INT_SIZE * 8 ? intval(base_convert($value, 2, 10)) : $value ;
                }
                return $meta['len'] < PHP_INT_SIZE * 8 ? intval($value) : $value ;

            case 'bool':             // pgsql
                return boolval($value);

            case 'decimal':          // mysql, sqlsrv, dblib
            case 'newdecimal':       // mysql
            case 'numeric':          // pgsql, sqlsrv, dblib
                return Decimal::of($value);

            case 'year':             // mysql
                return intval($value);

            case 'date':             // mysql, pgsql, dblib
            case 'newdate':          // mysql
                return $value === '0000-00-00' ? null : Date::createDateTime($value, ['Y-m-d']) ;

            case 'timestamp':        // sqlsrv, mysql, pgsql
                if ($driver === 'sqlsrv') { // timestamp is rowversion in sqlsrv, it is not DateTime.
                    return bin2hex($value);
                }
                // no break
            case 'datetime':         // sqlsrv, mysql, dblib
                return $value === '0000-00-00 00:00:00' ? null : DateTime::createDateTime($value, ['Y-m-d H:i:s.u', 'Y-m-d H:i:s']) ;

            case 'datetime2':        // sqlsrv, dblib
                $value = $meta['precision'] > 6 ? preg_replace('/(\.[0-9]{6})[0-9]+/', '$1', $value) : $value ;
                return DateTime::createDateTime($value, ['Y-m-d H:i:s.u', 'Y-m-d H:i:s']) ;

            case 'smalldatetime':    // sqlsrv, dblib
                return DateTime::createDateTime($value, ['Y-m-d H:i:s', 'Y-m-d H:i']) ;

            case 'datetimeoffset':   // sqlsrv, dblib
                $value = $meta['precision'] > 6 ? preg_replace('/(\.[0-9]{6})[0-9]+/', '$1', $value) : $value ;
                return DateTime::createDateTime($value, ['Y-m-d H:i:s.u P', 'Y-m-d H:i:s.up', 'Y-m-d H:i:s P', 'Y-m-d H:i:sp']) ;

            case 'timestamptz':      // pgsql
                return DateTime::createDateTime($value, ['Y-m-d H:i:sO', 'Y-m-d H:i:sT']) ;

            // case 'set':              // mysql (It not works currently because of mysql PDO return 'string' for SET column)
            //     return explode(',', $value);

            // case 'enum':             // mysql (It not works currently because of mysql PDO return 'string' for ENUM column)
            //     return (string)$value;

            case 'xml':              // sqlsrv, pgsql, dblib
                return new \SimpleXMLElement($value);

            case 'json':             // pgsql
            case 'jsonb':            // pgsql
                return json_decode($value, true);

            case 'time':             // sqlsrv, mysql, pgsql, dblib
            case 'timetz':           // pgsql
            case 'interval':         // pgsql
            case 'tinterval':        // pgsql
                // @todo Implements Time and Interval class and incorporate
                return (string)$value;

            case 'udt':              // sqlsrv(hierarchyid, geometry, geography)
                return null;

            case 'geometry':         // mysql, dblib
            case 'box':              // pgsql
            case 'circle':           // pgsql
            case 'line':             // pgsql
            case 'lseg':             // pgsql
            case 'path':             // pgsql
            case 'point':            // pgsql
            case 'polygon':          // pgsql
                // @todo Select and incorporate geometry library
                return (string)$value;

            case 'cidr':             // pgsql (IPv4 or IPv6)
            case 'inet':             // pgsql (Host address of IPv4 or IPv6)
            case 'macaddr':          // pgsql
            case 'macaddr8':         // pgsql
                return (string)$value;

            case 'money':            // sqlsrv, dblib, pgsql
            case 'smallmoney':       // sqlsrv, dblib
                // @todo Should we remove the currency unit and return a Decimal class, or should we implement a Money class that extended Decimal
                return (string)$value;

            case 'bytea':            // pgsql
            case 'tiny_blob':        // mysql
            case 'medium_blob':      // mysql
            case 'long_blob':        // mysql
            case 'blob':             // mysql, sqlite
            case 'binary':           // sqlsrv, dblib
            case 'varbinary':        // sqlsrv, dblib
            case 'image':            // sqlsrv, dblib
                return $value;

            case 'tsquery':          // pgsql
            case 'tsvector':         // pgsql
            case 'gtsvector':        // pgsql
                return (string)$value;

            case 'txid_snapshot':    // pgsql
                return (string)$value;

            case 'unknown':          // ALL (dblib)
                return $value;
        }

        return $value;
    }
}

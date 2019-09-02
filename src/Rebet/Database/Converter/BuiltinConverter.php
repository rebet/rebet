<?php
namespace Rebet\Database\Converter;

use Rebet\Common\Decimal;
use Rebet\Database\Database;
use Rebet\Database\Expression;
use Rebet\Database\PdoParameter;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Enum\Enum;

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
     * {@inheritDoc}
     */
    public function toPdoType(Database $db, $value) : PdoParameter
    {
        if ($value instanceof Enum) {
            $value = $value->value;
        }
        if ($value instanceof Expression) {
            $value = $value->value;
        }

        $driver_name = $db->driverName();
        switch (true) {
            case $value instanceof PdoParameter:       return $value;
            case $value === null:                      return PdoParameter::null();
            case is_bool($value):                      return $driver_name === 'mysql' ? PdoParameter::int($value ? 1 : 0) : PdoParameter::bool($value);
            case is_int($value):                       return PdoParameter::int($value);
            case is_float($value):                     return PdoParameter::str($value);
            case is_resource($value):                  return PdoParameter::lob($value);
            case $value instanceof Date:               return PdoParameter::str($value->format("Y-m-d"));
            case $value instanceof \DateTimeInterface: return PdoParameter::str($value->format($driver_name === 'pgsql' ? "Y-m-d H:i:sO" : "Y-m-d H:i:s"));
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
     */
    public function toPhpType(Database $db, $value, array $meta = [], ?string $type = null)
    {
        if ($value === null) {
            return null;
        }
        if ($type !== null) {
            return Reflector::convert($value, $type);
        }
        switch (strtolower($meta['native_type'] ?? 'unknown')) {
            case 'null':             // mysql, sqlite
                return null;

            case 'string':           // mysql, sqlite
            case 'var_string':       // mysql
            case 'text':             // pgsql, dblib
            case 'varchar':          // pgsql, dblib
            case 'bpchar':           // pgsql
            case 'uuid':             // pgsql
            case 'pg_lsn':           // pgsql
            case 'nvarchar':         // dblib
            case 'char':             // dblib
            case 'ntext':            // dblib
            case 'nchar':            // dblib
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
            case 'uniqueidentifier': // dblib
            case 'binary':           // dblib
            case 'tinyint':          // dblib
            case 'smallint':         // dblib
            case 'int':              // dblib
                return intval($value) ;

            case 'longlong':         // mysql
            case 'bigint':           // dblib
                return PHP_INT_SIZE === 8 ? intval($value) : (string)$value ;

            case 'float':            // mysql, dblib
            case 'double':           // mysql, sqlite
            case 'real':             // dblib
            case 'float4':           // pgsql
            case 'float8':           // pgsql
                return floatval($value);

            case 'bit':              // mysql, dblib
                return mb_strlen($value) <= PHP_INT_SIZE * 8 ? bindec($value) : (string)$value ;

            case 'bool':             // pgsql
                return boolval($value);

            case 'decimal':          // mysql, dblib
            case 'newdecimal':       // mysql
            case 'numeric':          // pgsql, dblib
                return Decimal::of($value);

            case 'year':             // mysql
                return intval($value);

            case 'date':             // mysql, pgsql, dblib
            case 'newdate':          // mysql
                return $value === '0000-00-00' ? null : Date::createFromFormat('!Y-m-d', $value) ;

            case 'timestamp':        // mysql, pgsql
            case 'datetime':         // mysql, dblib
            case 'datetime2':        // dblib
            case 'smalldatetime':    // dblib
                return $value === '0000-00-00 00:00:00' ? null : DateTime::createFromFormat('Y-m-d H:i:s', $value) ;

            case 'timestamptz':      // pgsql
                return DateTime::createFromFormat('Y-m-d H:i:sT', $value) ;

            // case 'set':              // mysql (It not works currently because of mysql PDO return 'string' for SET column)
            //     return explode(',', $value);

            // case 'enum':             // mysql (It not works currently because of mysql PDO return 'string' for ENUM column)
            //     return (string)$value;

            case 'xml':              // pgsql, dblib
                return new \SimpleXMLElement($value);

            case 'json':             // pgsql
            case 'jsonb':            // pgsql
                return json_decode($value, true);

            case 'time':             // mysql, pgsql, dblib
            case 'timetz':           // pgsql
            case 'datetimeoffset':   // dblib
            case 'interval':         // pgsql
            case 'tinterval':        // pgsql
                // @todo Implements Time and Interval class and incorporate
                return (string)$value;

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

            case 'money':            // dblib, pgsql
            case 'smallmoney':       // dblib
                // @todo Should we remove the currency unit and return a Decimal class, or should we implement a Money class that extended Decimal
                return (string)$value;

            case 'bytea':            // pgsql
            case 'tiny_blob':        // mysql
            case 'medium_blob':      // mysql
            case 'long_blob':        // mysql
            case 'blob':             // mysql, sqlite
            case 'varbinary':        // dblib
            case 'image':            // dblib
                return $value;

            case 'tsquery':          // pgsql
            case 'tsvector':         // pgsql
            case 'gtsvector':        // pgsql
                return (string)$value;

            case 'txid_snapshot':    // pgsql
                return (string)$value;

            case 'sql_variant':      // dblib
            case 'unknown':          // ALL (dblib)
                return $value;
        }

        return $value;
    }
}

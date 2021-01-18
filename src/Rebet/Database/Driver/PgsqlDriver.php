<?php
namespace Rebet\Database\Driver;

use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;

/**
 * PostgreSQL Driver Class
 *
 * Difference list of all predicates that depended on PostgreSQL,
 * NOTE: {@see Rebet\Database\Ransack\Ransack} for common predicates for Ransack Search.
 *
 * | No | Ransack Predicate   | Value Type   | Description                                                                          | Example emulated SQL
 * +----+---------------------+--------------+--------------------------------------------------------------------------------------+------------------------------------------
 * |  4 | {col}_{predicate}   | Any          | Custom predicate by PgsqlDriver `ransack.predicates` setting for PostgreSQL database | Anything you want
 * |    | {col}_matches       | String       | POSIX regex match                                                                    | ['title_matches'     => '^[0-9]+%'] => title ~ '^[0-9]+%'
 * |    | {col}_not_matches   | String       | POSIX regex not match                                                                | ['title_not_matches' => '^[0-9]+%'] => title !~ '^[0-9]+%'
 * |    | {col}_search        | String       | Full Text Search                                                                     | ['body_search'       => 'foo'     ] => to_tsvector(body) @@ to_tsquery('foo')
 * +    +---------------------+--------------+--------------------------------------------------------------------------------------+------------------------------------------
 * |    | *_{option} (option) | Any          | Custom option by PgsqlDriver `ransack.options` setting for PostgreSQL database       | Anything you want
 * |    | *_len      (option) | String       | Length option depend on configure                                                    | ['tag_eq_len'      =>     3] => LENGTH(tag) = 3
 * |    | *_uc       (option) | String       | Upper case option depend on configure                                                | ['tag_eq_uc'       => 'FOO'] => UPPER(tag) = 'FOO'
 * |    | *_lc       (option) | String       | Lower case option depend on configure                                                | ['tag_eq_lc'       => 'foo'] => LOWER(tag) = 'foo'
 * |    | *_age      (option) | DateTime     | Age option depend on configure                                                       | ['birthday_lt_age' =>    20] => DATE_PART('year', AGE(birthday)) < 20
 * |    | *_y        (option) | DateTime     | Year of date time option depend on configure                                         | ['entry_at_eq_y'   =>  2000] => DATE_PART('year', entry_at) = 2000
 * |    | *_m        (option) | DateTime     | Month of date time option depend on configure                                        | ['entry_at_eq_m'   =>    12] => DATE_PART('month', entry_at) = 12
 * |    | *_d        (option) | DateTime     | Day of date time option depend on configure                                          | ['entry_at_eq_d'   =>    12] => DATE_PART('day', entry_at) = 12
 * |    | *_h        (option) | DateTime     | Hour of date time option depend on configure                                         | ['entry_at_eq_h'   =>    12] => DATE_PART('hour', entry_at) = 12
 * |    | *_i        (option) | DateTime     | Minute of date time option depend on configure                                       | ['entry_at_eq_i'   =>    12] => DATE_PART('minute', entry_at) = 12
 * |    | *_s        (option) | DateTime     | Second of date time option depend on configure                                       | ['entry_at_eq_s'   =>    12] => DATE_PART('second', entry_at) = 12
 * |    | *_dow      (option) | DateTime     | Day of week option depend on configure                                               | ['entry_at_eq_dow' =>     1] => DATE_PART('dow', entry_at) = 1
 * +----+---------------------+--------------+--------------------------------------------------------------------------------------+------------------------------------------
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PgsqlDriver extends AbstractDriver
{
    public static function defaultConfig()
    {
        return [
            'options' => [
                'pdo' => [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ],
                'statement' => [
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ],
            ],
            'ransack' => [
                'value_converters' => [],
                'predicates'       => [
                    'matches'     => ["{col} ~ {val}"                           , null , 'OR' ],
                    'not_matches' => ["{col} !~ {val}"                          , null , 'AND'],
                    'search'      => ["to_tsvector({col}) @@ to_tsquery({val})" , null , 'OR' ],
                ],
                'options'          => [
                    'len' => 'LENGTH({col})',
                    'uc'  => 'UPPER({col})',
                    'lc'  => 'LOWER({col})',
                    'age' => "DATE_PART('year', AGE({col}))",
                    'y'   => "DATE_PART('year', {col})",
                    'm'   => "DATE_PART('month', {col})",
                    'd'   => "DATE_PART('day', {col})",
                    'h'   => "DATE_PART('hour', {col})",
                    'i'   => "DATE_PART('minute', {col})",
                    's'   => "DATE_PART('second', {col})",
                    'dow' => "DATE_PART('dow', {col})",
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected const SUPPORTED_PDO_DRIVER = 'pgsql';

    /**
     * {@inheritDoc}
     */
    protected const PDO_DATETIME_FORMAT = 'Y-m-d H:i:sO';

    /**
     * {@inheritDoc}
     */
    public function truncate(string $table_name, ?bool $with_vacuum = true) : string
    {
        $quoted_table_name = $this->quoteIdentifier($table_name);
        $this->exec($sql = "TRUNCATE TABLE {$quoted_table_name} RESTART IDENTITY");
        return $sql;
    }

    /**
     * {@inheritDoc}
     *
     * @see 'pgsql'  native_type from http://gcov.php.net/PHP_7_4/lcov_html/ext/pdo_pgsql/pgsql_statement.c.gcov.php and `SELECT TYPNAME FROM PG_TYPE` results.
     */
    public function toPhpType($value, array $meta = [], ?string $type = null)
    {
        if ($value === null) {
            return null;
        }
        if ($type !== null) {
            return Reflector::convert($value, $type);
        }

        switch ($native_type = strtolower($meta['native_type'] ?? '(native_type missing)')) {
            case 'text':
            case 'varchar':
            case 'bpchar':
            case 'uuid':
            case 'pg_lsn':
                return (string)$value;

            case 'int2':
            case 'int4':
            case 'int8':
            case 'varbit':
                return intval($value) ;

            case 'float4':
            case 'float8':
                return floatval($value);

            case 'bit':
                return $meta['precision'] < PHP_INT_SIZE * 8 ? intval(base_convert($value, 2, 10)) : $value ;

            case 'bool':
                return boolval($value);

            case 'numeric':
                return Decimal::of($value);

            case 'date':
                return Date::createDateTime($value, ['Y-m-d']);

            case 'timestamp':
                // @todo Check available formats
                return DateTime::createDateTime($value, ['Y-m-d H:i:s.u', 'Y-m-d H:i:s']) ;

            case 'timestamptz':
                // @todo Check available formats
                return DateTime::createDateTime($value, ['Y-m-d H:i:sO', 'Y-m-d H:i:sT']) ;

            case 'xml':
                return new \SimpleXMLElement($value);

            case 'json':
            case 'jsonb':
                return json_decode($value, true);

            case 'time':
            case 'timetz':
            case 'interval':
            case 'tinterval':
                // @todo Implements Time and Interval class and incorporate
                return (string)$value;

            case 'box':
            case 'circle':
            case 'line':
            case 'lseg':
            case 'path':
            case 'point':
            case 'polygon':
                // @todo Select and incorporate geometry library
                return (string)$value;

            case 'cidr':     // (IPv4 or IPv6)
            case 'inet':     // (Host address of IPv4 or IPv6)
            case 'macaddr':
            case 'macaddr8':
                return (string)$value;

            case 'money':
                // @todo Should we remove the currency unit and return a Decimal class, or should we implement a Money class that extended Decimal
                return (string)$value;

            case 'bytea':
                return $value;

            case 'tsquery':
            case 'tsvector':
            case 'gtsvector':
                return (string)$value;

            case 'txid_snapshot':
                return (string)$value;
        }

        // trigger_error("[".static::SUPPORTED_PDO_DRIVER."] Unknown native type '{$native_type}' found.", E_USER_NOTICE);
        return $value;
    }
}

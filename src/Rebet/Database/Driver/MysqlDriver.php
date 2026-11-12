<?php

declare(strict_types=1);

namespace Rebet\Database\Driver;

use Override;
use Rebet\Database\PdoParameter;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;

/**
 * MySQL Driver Class
 *
 * Difference list of all predicates that depended on MySQL,
 * NOTE: {@see Rebet\Database\Ransack\Ransack} for common predicates for Ransack Search.
 *
 * | No | Ransack Predicate   | Value Type   | Description                                                                     | Example emulated SQL
 * +----+---------------------+--------------+---------------------------------------------------------------------------------+------------------------------------------
 * |  4 | {col}_{predicate}   | Any          | Custom predicate by MysqlDriver `ransack.predicates` setting for MySql database | Anything you want
 * |    | {col}_matches       | String       | POSIX regex match                                                               | ['title_matches'     => '^[0-9]+%'] => title REGEXP '^[0-9]+%'
 * |    | {col}_not_matches   | String       | POSIX regex not match                                                           | ['title_not_matches' => '^[0-9]+%'] => title NOT REGEXP '^[0-9]+%'
 * |    | {col}_search        | String       | Full Text Search                                                                | ['body_search'       => 'foo'     ] => MATCH(body) AGAINST('foo')
 * +    +---------------------+--------------+---------------------------------------------------------------------------------+------------------------------------------
 * |    | *_{option} (option) | Any          | Custom option by MysqlDriver `ransack.options` setting for MySql database       | Anything you want
 * |    | *_bin      (option) | String       | Binary option depend on configure                                               | ['body_contains_bin' => 'foo'] => BINARY body LIKE '%foo%' ESCAPE '|'
 * |    | *_cs       (option) | String       | Case sensitive option depend on configure                                       | ['body_contains_cs'  => 'foo'] => body COLLATE utf8mb4_bin LIKE '%foo%' ESCAPE '|'
 * |    | *_ci       (option) | String       | Case insensitive option depend on configure                                     | ['body_contains_ci'  => 'foo'] => body COLLATE utf8mb4_general_ci LIKE '%foo%' ESCAPE '|'
 * |    | *_fs       (option) | String       | Fuzzy search option depend on configure                                         | ['body_contains_fs'  => 'foo'] => body COLLATE utf8mb4_unicode_ci LIKE '%foo%' ESCAPE '|'
 * |    | *_len      (option) | String       | Length option depend on configure                                               | ['tag_eq_len'        =>     3] => CHAR_LENGTH(tag) = 3
 * |    | *_uc       (option) | String       | Upper case option depend on configure                                           | ['tag_eq_uc'         => 'FOO'] => UPPER(tag) = 'FOO'
 * |    | *_lc       (option) | String       | Lower case option depend on configure                                           | ['tag_eq_lc'         => 'foo'] => LOWER(tag) = 'foo'
 * |    | *_age      (option) | DateTime     | Age option depend on configure                                                  | ['birthday_lt_age'   =>    20] => TIMESTAMPDIFF(YEAR, birthday, CURRENT_DATE) < 20
 * |    | *_y        (option) | DateTime     | Year of date time option depend on configure                                    | ['entry_at_eq_y'     =>  2000] => YEAR(entry_at) = 2000
 * |    | *_m        (option) | DateTime     | Month of date time option depend on configure                                   | ['entry_at_eq_m'     =>    12] => MONTH(entry_at) = 12
 * |    | *_d        (option) | DateTime     | Day of date time option depend on configure                                     | ['entry_at_eq_d'     =>    12] => DAY(entry_at) = 12
 * |    | *_h        (option) | DateTime     | Hour of date time option depend on configure                                    | ['entry_at_eq_h'     =>    12] => HOUR(entry_at) = 12
 * |    | *_i        (option) | DateTime     | Minute of date time option depend on configure                                  | ['entry_at_eq_i'     =>    12] => MINUTE(entry_at) = 12
 * |    | *_s        (option) | DateTime     | Second of date time option depend on configure                                  | ['entry_at_eq_s'     =>    12] => SECOND(entry_at) = 12
 * |    | *_dow      (option) | DateTime     | Day of week option depend on configure                                          | ['entry_at_eq_dow'   =>     1] => DAYOFWEEK(entry_at) = 1
 * +----+---------------------+--------------+---------------------------------------------------------------------------------+------------------------------------------
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MysqlDriver extends AbstractDriver
{
    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/skeltons/app/config/database.lp.php
     */
    #[Override]
    public static function defaultConfig()
    {
        return [
            'options' => [
                'pdo'       => [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                    \PDO::ATTR_AUTOCOMMIT         => false,
                ],
                'statement' => [
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ],
            ],
            'ransack' => [
                'value_converters' => [],
                'predicates'       => [
                    'matches'     => ["{col} REGEXP {val}", null, 'OR' ],
                    'not_matches' => ["{col} NOT REGEXP {val}", null, 'AND'],
                    'search'      => ["MATCH({col}) AGAINST({val})", null, 'OR' ],
                ],
                'options'          => [
                    'bin' => 'BINARY {col}',
                    'cs'  => '{col} COLLATE utf8mb4_bin',
                    'ci'  => '{col} COLLATE utf8mb4_general_ci',
                    'fs'  => '{col} COLLATE utf8mb4_unicode_ci',
                    'len' => 'CHAR_LENGTH({col})',
                    'uc'  => 'UPPER({col})',
                    'lc'  => 'LOWER({col})',
                    'age' => 'TIMESTAMPDIFF(YEAR, {col}, CURRENT_DATE)',
                    'y'   => 'YEAR({col})',
                    'm'   => 'MONTH({col})',
                    'd'   => 'DAY({col})',
                    'h'   => 'HOUR({col})',
                    'i'   => 'MINUTE({col})',
                    's'   => 'SECOND({col})',
                    'dow' => 'DAYOFWEEK({col})',
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected const SUPPORTED_PDO_DRIVER = 'mysql';

    /**
     * {@inheritDoc}
     */
    protected const IDENTIFIER_QUOTES = ['`', '`'];

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function toPdoType($value): PdoParameter
    {
        return match (true) {
            is_bool($value) => PdoParameter::int($value ? 1 : 0),
            default         => parent::toPdoType($value),
        };
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $meta
     * @see 'mysql'  native_type from http://gcov.php.net/PHP_7_4/lcov_html/ext/pdo_mysql/mysql_statement.c.gcov.php
     */
    #[Override]
    public function toPhpType($value, array $meta = [], string|null $type = null)
    {
        if ($value === null) {
            return null;
        }
        if ($type !== null) {
            return Reflector::convert($value, $type);
        }

        $native_type = strtolower($meta['native_type'] ?? '(native_type missing)');

        return match ($native_type) {
            'null'                                          => null,
            'string', 'var_string'                          => (string) $value,
            'tiny'                                          => $meta['len'] === 1 ? boolval($value) : intval($value),
            'short', 'long', 'int24'                        => intval($value),
            'longlong'                                      => PHP_INT_SIZE === 8 ? intval($value) : (string) $value,
            'float', 'double'                               => floatval($value),
            'bit'                                           => $meta['len'] < PHP_INT_SIZE * 8 ? intval($value) : $value,
            'decimal', 'newdecimal'                         => Decimal::of($value),
            'year'                                          => intval($value),
            'date', 'newdate'                               => $value === '0000-00-00' ? null : Date::createDateTime($value, ['Y-m-d']),
            'timestamp', 'datetime'                         => $value === '0000-00-00 00:00:00' ? null : DateTime::createDateTime($value, ['Y-m-d H:i:s.u', 'Y-m-d H:i:s']),
            // @todo mysql (It not works currently because of mysql PDO return 'string' for SET column)
            // 'set'                                           => explode(',', $value),
            // @todo mysql (It not works currently because of mysql PDO return 'string' for ENUM column)
            // 'enum'                                          => (string) $value,
            // @todo Implements Time and Interval class and incorporate
            'time'                                          => (string) $value,
            // @todo Select and incorporate geometry library
            'geometry'                                      => (string) $value,
            'tiny_blob', 'medium_blob', 'long_blob', 'blob' => $value,
            // Unknown native type (it is returned as it is) : trigger_error("[".static::SUPPORTED_PDO_DRIVER."] Unknown native type '{$native_type}' found.", E_USER_NOTICE);
            default                                         => $value,
        };
    }
}

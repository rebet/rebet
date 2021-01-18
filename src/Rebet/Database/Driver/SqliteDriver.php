<?php
namespace Rebet\Database\Driver;

use Rebet\Database\Exception\DatabaseException;
use Rebet\Tools\Reflection\Reflector;

/**
 * SQLite3 Driver Class
 *
 * Difference list of all predicates that depended on SQLite3,
 * NOTE: {@see Rebet\Database\Ransack\Ransack} for common predicates for Ransack Search.
 *
 * | No | Ransack Predicate   | Value Type   | Description                                                                        | Example emulated SQL
 * +----+---------------------+--------------+------------------------------------------------------------------------------------+------------------------------------------
 * |  4 | {col}_{predicate}   | Any          | Custom predicate by SqliteDriver `ransack.predicates` setting for SQLite3 database | Anything you want
 * |    | {col}_matches       | String       | POSIX regex match     (need extension)                                             | ['title_matches'     => '^[0-9]+%'] => title REGEXP '^[0-9]+%'
 * |    | {col}_not_matches   | String       | POSIX regex not match (need extension)                                             | ['title_not_matches' => '^[0-9]+%'] => title NOT REGEXP '^[0-9]+%'
 * |    | {col}_search        | String       | Full Text Search      (need extension)                                             | ['body_search'       => 'foo'     ] => body MATCH 'foo'
 * +    +---------------------+--------------+------------------------------------------------------------------------------------+------------------------------------------
 * |    | *_{option} (option) | Any          | Custom option by SqliteDriver `ransack.options` setting for SQLite3 database       | Anything you want
 * |    | *_bin      (option) | String       | Binary option depend on configure                                                  | ['body_contains_bin' => 'foo'] => BINARY body LIKE '%foo%' ESCAPE '|'
 * |    | *_ci       (option) | String       | Case insensitive option depend on configure                                        | ['body_contains_ci'  => 'foo'] => body COLLATE nocase LIKE '%foo%' ESCAPE '|'
 * |    | *_len      (option) | String       | Length option depend on configure                                                  | ['tag_eq_len'        =>     3] => LENGTH(tag) = 3
 * |    | *_uc       (option) | String       | Upper case option depend on configure                                              | ['tag_eq_uc'         => 'FOO'] => UPPER(tag) = 'FOO'
 * |    | *_lc       (option) | String       | Lower case option depend on configure                                              | ['tag_eq_lc'         => 'foo'] => LOWER(tag) = 'foo'
 * |    | *_age      (option) | DateTime     | Age option depend on configure                                                     | ['birthday_lt_age'   =>    20] => CAST((STRFTIME('%Y%m%d', 'now') - STRFTIME('%Y%m%d', birthday)) / 10000 AS int) < 20
 * |    | *_y        (option) | DateTime     | Year of date time option depend on configure                                       | ['entry_at_eq_y'     =>  2000] => STRFTIME('%Y', entry_at) = 2000
 * |    | *_m        (option) | DateTime     | Month of date time option depend on configure                                      | ['entry_at_eq_m'     =>    12] => STRFTIME('%m', entry_at) = 12
 * |    | *_d        (option) | DateTime     | Day of date time option depend on configure                                        | ['entry_at_eq_d'     =>    12] => STRFTIME('%d', entry_at) = 12
 * |    | *_h        (option) | DateTime     | Hour of date time option depend on configure                                       | ['entry_at_eq_h'     =>    12] => STRFTIME('%H', entry_at) = 12
 * |    | *_i        (option) | DateTime     | Minute of date time option depend on configure                                     | ['entry_at_eq_i'     =>    12] => STRFTIME('%M', entry_at) = 12
 * |    | *_s        (option) | DateTime     | Second of date time option depend on configure                                     | ['entry_at_eq_s'     =>    12] => STRFTIME('%S', entry_at) = 12
 * |    | *_dow      (option) | DateTime     | Day of week option depend on configure                                             | ['entry_at_eq_dow'   =>     1] => STRFTIME('%w', entry_at) = 1
 * +----+---------------------+--------------+------------------------------------------------------------------------------------+------------------------------------------
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SqliteDriver extends AbstractDriver
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
                    'matches'     => ["{col} REGEXP {val}"     , null , 'OR' ],
                    'not_matches' => ["{col} NOT REGEXP {val}" , null , 'AND'],
                    'search'      => ["{col} MATCH {val}"      , null , 'OR' ],
                ],
                'options'          => [
                    'bin' => 'BINARY {col}',
                    'ci'  => '{col} COLLATE nocase',
                    'len' => 'LENGTH({col})',
                    'uc'  => 'UPPER({col})',
                    'lc'  => 'LOWER({col})',
                    'age' => "CAST((STRFTIME('%Y%m%d', 'now') - STRFTIME('%Y%m%d', {col})) / 10000 AS int)",
                    'y'   => "STRFTIME('%Y', {col})",
                    'm'   => "STRFTIME('%m', {col})",
                    'd'   => "STRFTIME('%d', {col})",
                    'h'   => "STRFTIME('%H', {col})",
                    'i'   => "STRFTIME('%M', {col})",
                    's'   => "STRFTIME('%S', {col})",
                    'dow' => "STRFTIME('%w', {col})",
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected const SUPPORTED_PDO_DRIVER = 'sqlite';

    /**
     * {@inheritDoc}
     */
    public function truncate(string $table_name, ?bool $with_vacuum = true) : string
    {
        $sqls              = [];
        $quoted_table_name = $this->quoteIdentifier($table_name);
        $this->exec($sqls[] = "DELETE FROM {$quoted_table_name}");
        $this->prepare($sqls[] = "DELETE FROM sqlite_sequence WHERE name = :table_name")->execute(['table_name' => $table_name]);
        if ($with_vacuum) {
            $this->exec($sqls[] = "VACUUM");
        }
        return '/* TRUNCATE Emulate */ '.str_replace(':table_name', "'{$table_name}'", implode('; ', $sqls));
    }

    /**
     * {@inheritDoc}
     */
    public function appendForUpdate(string $sql) : string
    {
        throw new DatabaseException("SQLite does not support `FOR UPDATE`");
    }

    /**
     * {@inheritDoc}
     *
     * @see 'sqlite' native_type from http://gcov.php.net/PHP_7_4/lcov_html/ext/pdo_sqlite/sqlite_statement.c.gcov.php
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
            case 'null':
                return null;

            case 'string':
                return (string)$value;

            case 'integer':
                return intval($value) ;

            case 'double':
                return floatval($value);

            case 'blob':
                return $value;
        }

        // trigger_error("[".static::SUPPORTED_PDO_DRIVER."] Unknown native type '{$native_type}' found.", E_USER_NOTICE);
        return $value;
    }
}

<?php
namespace Rebet\Database\Driver;

use Rebet\Database\Exception\DatabaseException;
use Rebet\Tools\Reflection\Reflector;

/**
 * SQLite3 Driver Class
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

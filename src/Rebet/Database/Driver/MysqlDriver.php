<?php
namespace Rebet\Database\Driver;

use Rebet\Database\PdoParameter;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Enum\Enum;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;

/**
 * MySQL Driver Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MysqlDriver extends AbstractDriver
{
    public static function defaultConfig()
    {
        return [
            'options' => [
                'pdo' => [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                    \PDO::ATTR_AUTOCOMMIT         => false,
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
    protected const SUPPORTED_PDO_DRIVER = 'mysql';

    /**
     * {@inheritDoc}
     */
    protected const IDENTIFIER_QUOTES = ['`', '`'];

    /**
     * {@inheritDoc}
     */
    public function toPdoType($value) : PdoParameter
    {
        if ($value instanceof Enum) {
            $value = $value->value;
        }

        switch (true) {
            case is_bool($value): return PdoParameter::int($value ? 1 : 0);
        }

        return parent::toPdoType($value);
    }

    /**
     * {@inheritDoc}
     *
     * @see 'mysql'  native_type from http://gcov.php.net/PHP_7_4/lcov_html/ext/pdo_mysql/mysql_statement.c.gcov.php
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
            case 'var_string':
                return (string)$value;

            case 'tiny':
                return $meta['len'] === 1 ? boolval($value) : intval($value) ;

            case 'short':
            case 'long':
            case 'int24':
                return intval($value) ;

            case 'longlong':
                return PHP_INT_SIZE === 8 ? intval($value) : (string)$value ;

            case 'float':
            case 'double':
                return floatval($value);

            case 'bit':
                return $meta['len'] < PHP_INT_SIZE * 8 ? intval($value) : $value ;

            case 'decimal':
            case 'newdecimal':
                return Decimal::of($value);

            case 'year':
                return intval($value);

            case 'date':
            case 'newdate':
                return $value === '0000-00-00' ? null : Date::createDateTime($value, ['Y-m-d']) ;

            case 'timestamp':
            case 'datetime':
                return $value === '0000-00-00 00:00:00' ? null : DateTime::createDateTime($value, ['Y-m-d H:i:s.u', 'Y-m-d H:i:s']) ;

            // case 'set':              // mysql (It not works currently because of mysql PDO return 'string' for SET column)
            //     return explode(',', $value);

            // case 'enum':             // mysql (It not works currently because of mysql PDO return 'string' for ENUM column)
            //     return (string)$value;

            case 'time':
                // @todo Implements Time and Interval class and incorporate
                return (string)$value;

            case 'geometry':
                // @todo Select and incorporate geometry library
                return (string)$value;

            case 'tiny_blob':
            case 'medium_blob':
            case 'long_blob':
            case 'blob':
                return $value;
        }

        // trigger_error("[".static::SUPPORTED_PDO_DRIVER."] Unknown native type '{$native_type}' found.", E_USER_NOTICE);
        return $value;
    }
}

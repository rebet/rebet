<?php
namespace Rebet\Database\Driver;

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;
use Rebet\Database\PdoParameter;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Enum\Enum;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;

/**
 * SQL Server Driver Class
 *
 * Difference list of all predicates that depended on SQL Server,
 * NOTE: {@see Rebet\Database\Ransack\Ransack} for common predicates for Ransack Search.
 *
 * | No | Ransack Predicate   | Value Type   | Description                                                                           | Example emulated SQL
 * +----+---------------------+--------------+---------------------------------------------------------------------------------------+------------------------------------------
 * |  4 | {col}_{predicate}   | Any          | Custom predicate by SqlsrvDriver `ransack.predicates` setting for SQL Server database | Anything you want
 * |    | {col}_search        | String       | Full Text Search                                                                      | ['body_search'  => 'foo'] => CONTAINS(body, 'foo')
 * |    | {col}_meaning       | String       | Full Text Search by meaning                                                           | ['body_meaning' => 'foo'] => FREETEXT(body, 'foo')
 * +    +---------------------+--------------+---------------------------------------------------------------------------------------+------------------------------------------
 * |    | *_{option} (option) | Any          | Custom option by SqlsrvDriver `ransack.options` setting for SQL Server database       | Anything you want
 * |    | *_len      (option) | String       | Length option depend on configure                                                     | ['tag_eq_len'      =>     3] => LENGTH(tag) = 3
 * |    | *_uc       (option) | String       | Upper case option depend on configure                                                 | ['tag_eq_uc'       => 'FOO'] => UPPER(tag) = 'FOO'
 * |    | *_lc       (option) | String       | Lower case option depend on configure                                                 | ['tag_eq_lc'       => 'foo'] => LOWER(tag) = 'foo'
 * |    | *_age      (option) | DateTime     | Age option depend on configure                                                        | ['birthday_lt_age' =>    20] => DATEDIFF(yy, birthday, GETDATE()) - IIF(GETDATE() >= DATEADD(yy, DATEDIFF(yy, birthday, GETDATE()), birthday), 0, 1) < 20
 * |    | *_y        (option) | DateTime     | Year of date time option depend on configure                                          | ['entry_at_eq_y'   =>  2000] => YEAR(entry_at) = 2000
 * |    | *_m        (option) | DateTime     | Month of date time option depend on configure                                         | ['entry_at_eq_m'   =>    12] => MONTH(entry_at) = 12
 * |    | *_d        (option) | DateTime     | Day of date time option depend on configure                                           | ['entry_at_eq_d'   =>    12] => DAY(entry_at) = 12
 * |    | *_h        (option) | DateTime     | Hour of date time option depend on configure                                          | ['entry_at_eq_h'   =>    12] => DATEPART(hh, entry_at) = 12
 * |    | *_i        (option) | DateTime     | Minute of date time option depend on configure                                        | ['entry_at_eq_i'   =>    12] => DATEPART(mi, entry_at) = 12
 * |    | *_s        (option) | DateTime     | Second of date time option depend on configure                                        | ['entry_at_eq_s'   =>    12] => DATEPART(ss, entry_at) = 12
 * |    | *_dow      (option) | DateTime     | Day of week option depend on configure                                                | ['entry_at_eq_dow' =>     1] => DATEPART(dw, entry_at) = 1
 * +----+---------------------+--------------+---------------------------------------------------------------------------------------+------------------------------------------
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SqlsrvDriver extends AbstractDriver
{
    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/database.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'options' => [
                'pdo' => [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ],
                'statement' => [
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ],
            ],
            'ransack' => [
                'value_converters' => [],
                'predicates'       => [
                    'search'  => ["CONTAINS({col}, {val})" , null , 'OR' ],
                    'meaning' => ["FREETEXT({col}, {val})" , null , 'OR' ],
                ],
                'options'          => [
                    'len' => 'LEN({col})',
                    'uc'  => 'UPPER({col})',
                    'lc'  => 'LOWER({col})',
                    'age' => "DATEDIFF(yy, {col}, GETDATE()) - IIF(GETDATE() >= DATEADD(yy, DATEDIFF(yy, {col}, GETDATE()), {col}), 0, 1)",
                    'y'   => 'YEAR({col})',
                    'm'   => 'MONTH({col})',
                    'd'   => 'DAY({col})',
                    'h'   => 'DATEPART(hh, {col})',
                    'i'   => 'DATEPART(mi, {col})',
                    's'   => 'DATEPART(ss, {col})',
                    'dow' => 'DATEPART(dw, {col})',
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected const SUPPORTED_PDO_DRIVER = 'sqlsrv';

    /**
     * {@inheritDoc}
     */
    protected const IDENTIFIER_QUOTES = ['[', ']'];

    /**
     * {@inheritDoc}
     */
    public function appendLimitOffset(string $sql, ?int $limit, ?int $offset = null) : string
    {
        if (!$limit && !$offset) {
            return $sql;
        }
        if ($limit && !$offset) {
            return preg_replace("#^(([\s]*/\*[\s\S]*(?=\*/)\*/)|([\s]*--.*\n))*([\s]*SELECT)#iu", "$0 TOP {$limit}", $sql, 1);
        }
        // Will not check the given SQL has `ORDER BY` phrase or not here.
        // If the SQL does not have `ORDER BY` then throws Exception when execute SQL.
        $offset = $offset ? " OFFSET {$offset} ROWS"         : " OFFSET 0 ROWS" ;
        $limit  = $limit  ? " FETCH NEXT {$limit} ROWS ONLY" : "" ;
        return "{$sql}{$offset}{$limit}";
    }

    /**
     * {@inheritDoc}
     */
    public function appendForUpdate(string $sql) : string
    {
        $syntax = (new PHPSQLParser())->parse($sql);
        if ($syntax['FROM'][0]['alias']) {
            $syntax['FROM'][0]['alias']['name'] = $syntax['FROM'][0]['alias']['name'].' WITH(UPDLOCK)';
        } else {
            $syntax['FROM'][0]['table'] = $syntax['FROM'][0]['table'].' WITH(UPDLOCK)';
        }
        return (new PHPSQLCreator())->create($syntax);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildSavepointSql(string $name) : string
    {
        return "SAVE TRANSACTION {$name}";
    }

    /**
     * {@inheritDoc}
     */
    protected function buildRollbackToSavepointSql(string $name) : string
    {
        return "ROLLBACK TRANSACTION {$name}";
    }

    /**
     * {@inheritDoc}
     */
    public function toPdoType($value) : PdoParameter
    {
        if ($value instanceof Enum) {
            $value = $value->value;
        }

        switch (true) {
            case is_resource($value): return PdoParameter::lob(stream_get_contents($value), \PDO::SQLSRV_ENCODING_BINARY);
        }

        return parent::toPdoType($value);
    }

    /**
     * {@inheritDoc}
     *
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

        switch ($native_type = strtolower($meta['sqlsrv:decl_type'] ?? '(sqlsrv:decl_type missing)')) {
            case 'sql_variant':
                return (string)$value;

            case 'text':
            case 'varchar':
            case 'uniqueidentifier':
            case 'nvarchar':
            case 'char':
            case 'ntext':
            case 'nchar':
            case 'sql_variant':
                return (string)$value;

            case 'tinyint':
            case 'smallint':
            case 'int':
            case 'int identity':
                return intval($value) ;

            case 'bigint':
                return PHP_INT_SIZE === 8 ? intval($value) : (string)$value ;

            case 'float':
            case 'real':
                return floatval($value);

            case 'bit':
                return boolval($value);

            case 'decimal':
            case 'numeric':
                return Decimal::of($value);

            case 'date':
                return Date::createDateTime($value, ['Y-m-d']);

            case 'timestamp':
                return bin2hex($value);

            case 'datetime':
                return DateTime::createDateTime($value, ['Y-m-d H:i:s.u', 'Y-m-d H:i:s']);

            case 'datetime2':
                $value = $meta['precision'] > 6 ? preg_replace('/(\.[0-9]{6})[0-9]+/', '$1', $value) : $value ;
                return DateTime::createDateTime($value, ['Y-m-d H:i:s.u', 'Y-m-d H:i:s']) ;

            case 'smalldatetime':
                return DateTime::createDateTime($value, ['Y-m-d H:i:s', 'Y-m-d H:i']) ;

            case 'datetimeoffset':
                $value = $meta['precision'] > 6 ? preg_replace('/(\.[0-9]{6})[0-9]+/', '$1', $value) : $value ;
                return DateTime::createDateTime($value, ['Y-m-d H:i:s.u P', 'Y-m-d H:i:s.up', 'Y-m-d H:i:s P', 'Y-m-d H:i:sp']) ;

            case 'xml':
                return new \SimpleXMLElement($value);

            case 'time':
                // @todo Implements Time and Interval class and incorporate
                return (string)$value;

            case 'udt': // hierarchyid, geometry, geography (Can not convert)
                return null;

            case 'money':
            case 'smallmoney':
                // @todo Should we remove the currency unit and return a Decimal class, or should we implement a Money class that extended Decimal
                return (string)$value;

            case 'binary':
            case 'varbinary':
            case 'image':
                return $value;
        }

        // trigger_error("[".static::SUPPORTED_PDO_DRIVER."] Unknown native type '{$native_type}' found.", E_USER_NOTICE);
        return $value;
    }
}

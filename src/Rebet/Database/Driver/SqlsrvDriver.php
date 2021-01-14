<?php
namespace Rebet\Database\Driver;

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

/**
 * SQL Server Driver Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SqlsrvDriver extends AbstractDriver
{
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
}

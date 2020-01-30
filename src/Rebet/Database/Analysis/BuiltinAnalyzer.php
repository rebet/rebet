<?php
namespace Rebet\Database\Analysis;

use PHPSQLParser\builders\ColumnReferenceBuilder;
use PHPSQLParser\builders\ConstantBuilder;
use PHPSQLParser\builders\FunctionBuilder;
use PHPSQLParser\builders\ReservedBuilder;
use PHPSQLParser\builders\SelectBracketExpressionBuilder;
use PHPSQLParser\builders\SelectExpressionBuilder;
use PHPSQLParser\PHPSQLParser;
use Rebet\Database\Database;

/**
 * Builtin SQL Analyzer Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinAnalyzer implements Analyzer
{
    /**
     * @var Database
     */
    protected $db;

    /**
     * @var PHPSQLParser
     */
    protected $parser;

    /**
     * @var array of extract alias select column cache
     */
    protected $cache = [];

    /**
     * Create SQL Analyze instance
     *
     * @param Database $db
     * @param string $sql
     */
    protected function __construct(Database $db, string $sql)
    {
        $this->db     = $db;
        $this->parser = new PHPSQLParser($sql);
    }

    /**
     * {@inheritDoc}
     */
    public static function of(Database $db, string $sql) : Analyzer
    {
        return new static($db, $sql);
    }

    /**
     * {@inheritDoc}
     */
    public function isUnion() : bool
    {
        return isset($this->parser->parsed['UNION ALL']) || isset($this->parser->parsed['UNION']);
    }

    /**
     * {@inheritDoc}
     */
    public function hasWhere() : bool
    {
        return isset($this->parser->parsed['WHERE']);
    }

    /**
     * {@inheritDoc}
     */
    public function hasHaving() : bool
    {
        return isset($this->parser->parsed['HAVING']);
    }

    /**
     * {@inheritDoc}
     */
    public function hasGroupBy() : bool
    {
        return isset($this->parser->parsed['GROUP']);
    }

    // @todo Consider about relationship data cache and auto cache clear.
    // public function isInsert() : bool
    // {
    //     return isset($this->parser->parsed['INSERT']);
    // }

    // public function isUpdate() : bool
    // {
    //     return isset($this->parser->parsed['UPDATE']);
    // }

    // public function isDelete() : bool
    // {
    //     return isset($this->parser->parsed['DELETE']);
    // }

    // public function isReplace() : bool
    // {
    //     return isset($this->parser->parsed['REPLACE']);
    // }

    // protected static function pickTableNameFrom(array $token) : string
    // {
    //     $delim = $token['no_quotes']['delim'] ?? null;
    //     return $delim ? implode($delim, $token['no_quotes']['parts']) : $token['no_quotes']['parts'][0] ;
    // }

    // /**
    //  * {@inheritDoc}
    //  */
    // public function affectedTables() : array
    // {
    //     if ($this->isInsert()) {
    //         foreach ($this->parser->parsed['INSERT'] ?? [] as $token) {
    //             if ($token['expr_type'] === 'table') {
    //                 return [static::pickTableNameFrom($token)];
    //             }
    //         }
    //     }

    //     if ($this->isUpdate()) {
    //         $tables = [];
    //         foreach ($this->parser->parsed['UPDATE'] ?? [] as $token) {
    //             if ($token['expr_type'] === 'table') {
    //                 $tables[] = static::pickTableNameFrom($token);
    //             }
    //         }
    //         if (!empty($tables)) {
    //             return $tables;
    //         }
    //     }

    //     if ($this->isDelete()) {
    //         $tables  = [];
    //         $aliases = $this->parser->parsed['DELETE']['tables'] ?? false;
    //         if ($aliases) {
    //             foreach ($this->parser->parsed['FROM'] as $token) {
    //                 if (in_array($alias = $token['alias']['name'] ?? null, $aliases)) {
    //                     $tables[] = static::pickTableNameFrom($token);
    //                     $aliases  = array_diff($aliases, [$alias]);
    //                 }
    //             }
    //             $tables = array_merge($tables, $aliases);
    //             return $tables;
    //         }

    //         foreach ($this->parser->parsed['FROM'] ?? [] as $token) {
    //             if ($token['expr_type'] === 'table') {
    //                 $tables[] = static::pickTableNameFrom($token);
    //             }
    //         }
    //         if (!empty($tables)) {
    //             return $tables;
    //         }
    //     }

    //     if ($this->isReplace()) {
    //         foreach ($this->parser->parsed['REPLACE'] ?? [] as $token) {
    //             if ($token['expr_type'] === 'table') {
    //                 return [static::pickTableNameFrom($token)];
    //             }
    //         }
    //     }

    //     return [];
    // }

    /**
     * {@inheritDoc}
     */
    public function extractAliasSelectColumn(string $alias) : string
    {
        if (isset($this->cache[$alias])) {
            return $this->cache[$alias];
        }

        $real = $alias;
        if (!$this->isUnion()) {
            foreach ($this->parser->parsed['SELECT'] as $parsed) {
                if (($parsed['alias']['name'] ?? null) !== $alias) {
                    continue;
                }
                $real = $this->build($parsed);
                break;
            }
        }

        $this->cache[$alias] = $real;
        return $real;
    }

    /**
     * Build selection real expressions from parsed tree.
     *
     * @param array $parsed
     * @return string
     */
    protected function build(array $parsed) : string
    {
        $sql = "";
        $sql .= (new class extends ColumnReferenceBuilder {
            protected function buildAlias($parsed)
            {
                return '';
            }
        })->build($parsed);
        $sql .= (new class extends SelectBracketExpressionBuilder {
            protected function buildAlias($parsed)
            {
                return '';
            }
        })->build($parsed);
        $sql .= (new class extends SelectExpressionBuilder {
            protected function buildAlias($parsed)
            {
                return '';
            }
        })->build($parsed);
        $sql .= (new class extends FunctionBuilder {
            protected function buildAlias($parsed)
            {
                return '';
            }
        })->build($parsed);
        $sql .= (new class extends ConstantBuilder {
            protected function buildAlias($parsed)
            {
                return '';
            }
        })->build($parsed);
        $sql .= (new ReservedBuilder())->build($parsed);

        return $sql;
    }
}

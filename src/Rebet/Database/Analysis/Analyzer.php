<?php
namespace Rebet\Database\Analysis;

use Rebet\Database\Database;

/**
 * SQL Analyzer Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Analyzer
{
    /**
     * Get Analyzer of given database sql.
     *
     * @param Database $db
     * @param string $sql
     * @return self
     */
    public static function of(Database $db, string $sql) : self;

    /**
     * It checks the target sql is top level UNION [ALL] clause.
     *
     * @return bool
     */
    public function isUnion() : bool;

    /**
     * It checks the target sql has top level WHERE clause.
     *
     * @return bool
     */
    public function hasWhere() : bool;

    /**
     * It checks the target sql has top level HAVING clause.
     *
     * @return bool
     */
    public function hasHaving() : bool;

    /**
     * It checks the target sql has top level GROUP BY clause.
     *
     * @return bool
     */
    public function hasGroupBy() : bool;

    // @todo Consider about relationship data cache and auto cache clear.
    // /**
    //  * Get affected table name by SQL that data will be changed.
    //  * NOTE: If the SQL is SELECT statement then this method return empty array.
    //  *
    //  * @return string[]
    //  */
    // public function affectedTables() : array;

    /**
     * Extract the actual statement (real column name / expression / CASE statement / subquery, etc.) of the column that is aliased in the top level SELECT clause.
     * If the given name is not alias or the given sql is UNION then return given alias as it is.
     *
     * @param string $alias
     * @return string
     */
    public function extractAliasSelectColumn(string $alias) : string;
}

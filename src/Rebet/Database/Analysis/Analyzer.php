<?php
namespace Rebet\Database\Analysis;

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

    /**
     * It checks the target sql has top level ORDER BY clause.
     *
     * @return bool
     */
    public function hasOrderBy() : bool;

    /**
     * Extract the actual statement (real column name / expression / CASE statement / subquery, etc.) of the column that is aliased in the top level SELECT clause.
     * If the given name is not alias or the given sql is UNION then return given alias as it is.
     *
     * @param string $alias
     * @return string
     */
    public function extractAliasSelectColumn(string $alias) : string;
}

<?php
namespace Rebet\Database;

/**
 * Condition Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Condition
{
    /**
     * Partial condition SQL sentence.
     *
     * @var string
     */
    protected $sql = '';

    /**
     * Partial condition SQL parameters
     *
     * @var array
     */
    protected $params = [];

    /**
     * Create condition query parts.
     *
     * @param string $sql
     * @param array $params
     */
    public function __construct(string $sql, array $params = [])
    {
        $this->sql    = $sql;
        $this->params = $params;
    }

    /**
     * Get partial condition SQL sentence.
     *
     * @return string
     */
    public function sql() : string
    {
        return $this->sql;
    }

    /**
     * Get partial condition SQL parameters
     *
     * @return array
     */
    public function params() : array
    {
        return $this->params;
    }

    /**
     * Condition to where sentence.
     *
     * @return string
     */
    public function where() : string
    {
        return empty($this->sql) ? '' : " WHERE {$this->sql}" ;
    }
}

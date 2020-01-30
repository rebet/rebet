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
    public $sql = '';

    /**
     * Partial condition SQL parameters
     *
     * @var array
     */
    public $params = [];

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
     * Condition to where sentence.
     *
     * @return string
     */
    public function where() : string
    {
        return empty($this->sql) ? '' : " WHERE {$this->sql}" ;
    }
}

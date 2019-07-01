<?php
namespace Rebet\Database;

/**
 * PDO Parameter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PdoParameter
{
    /**
     * @var int type of PDO::PARAM_*
     */
    public $type;

    /**
     * @var mixed value of PDO
     */
    public $value;

    /**
     * Create PDO Type instance
     *
     * @param mixed $value
     * @param int $type (default: \PDO::PARAM_STR)
     */
    public function __construct($value, int $type = \PDO::PARAM_STR)
    {
        $this->value = $value;
        $this->type  = $type;
    }
}

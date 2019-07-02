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

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        $type_label = static::typeToLabel($this->type);
        return "[$type_label] {$this->value}";
    }

    /**
     * Convert type code to human readable label.
     *
     * @param integer $type
     * @return void
     */
    protected static function typeToLabel(int $type)
    {
        switch ($type) {
            case \PDO::PARAM_STR:  return 'STR';
            case \PDO::PARAM_INT:  return 'INT';
            case \PDO::PARAM_BOOL: return 'BOOL';
            case \PDO::PARAM_LOB:  return 'LOB';
            case \PDO::PARAM_NULL: return 'NULL';
        }
        return "TYPE({$type})";
    }
}

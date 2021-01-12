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
     * Driver option.
     *
     * @var mixed
     */
    public $option;

    /**
     * Create PDO Type instance
     *
     * @param mixed $value
     * @param mixed $option for driver (default: null)
     * @param int $type (default: \PDO::PARAM_STR)
     */
    public function __construct($value, int $type = \PDO::PARAM_STR, $option = null)
    {
        $this->value  = $value;
        $this->type   = $type;
        $this->option = $option;
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

    /**
     * Create string (PDO::PARAM_STR) type parameter.
     *
     * @param mixed $value
     * @return self
     */
    public static function str($value, $option = null) : self
    {
        return new static($value, \PDO::PARAM_STR, $option);
    }

    /**
     * Create integer (PDO::PARAM_INT) type parameter.
     *
     * @param mixed $value
     * @return self
     */
    public static function int($value, $option = null) : self
    {
        return new static($value, \PDO::PARAM_INT, $option);
    }

    /**
     * Create boolean (PDO::PARAM_BOOL) type parameter.
     *
     * @param mixed $value
     * @return self
     */
    public static function bool($value, $option = null) : self
    {
        return new static($value, \PDO::PARAM_BOOL, $option);
    }

    /**
     * Create lob (PDO::PARAM_LOB) type parameter.
     *
     * @param mixed $value
     * @return self
     */
    public static function lob($value, $option = null) : self
    {
        return new static($value, \PDO::PARAM_LOB, $option);
    }

    /**
     * Create null (PDO::PARAM_NULL) type parameter.
     *
     * @return self
     */
    public static function null($option = null) : self
    {
        return new static(null, \PDO::PARAM_NULL, $option);
    }
}

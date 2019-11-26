<?php
namespace Rebet\Database;

/**
 * Expression Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Expression
{
    /**
     * @var string of SQL expression
     */
    public $expression;

    /**
     * @var mixed value
     */
    public $value;

    /**
     * Create Expression Value instance
     *
     * @param string $expression template that contains '{val}' placeholder like 'GeomFromText({val})' or just function like 'now()'
     * @param mixed $value (default: null)
     */
    public function __construct(string $expression, $value = null)
    {
        $this->expression = $expression;
        $this->value      = $value;
    }

    /**
     * Create Expression
     *
     * @param string $expression template that contains '{val}' placeholder like 'GeomFromText({val})' or just function like 'now()'
     * @param mixed $value (default: null)
     * @return self
     */
    public static function of(string $expression, $value = null) : self
    {
        return new static($expression, $value);
    }
}

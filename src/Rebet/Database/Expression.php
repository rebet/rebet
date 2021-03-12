<?php
namespace Rebet\Database;

use Rebet\Database\Driver\Driver;
use Rebet\Tools\Utility\Strings;

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
    protected $expression;

    /**
     * @var array value
     */
    protected $values;

    /**
     * Create Expression Value instance
     *
     * @param string $expression template that contains '{values index}' placeholder like 'GeomFromText({0})' or just function like 'now()'
     * @param mixed[] ...$values
     */
    public function __construct(string $expression, ...$values)
    {
        $this->expression = $expression;
        $this->values     = $values;
    }

    /**
     * Create Expression
     *
     * @param string $expression template that contains '{values index}' placeholder like 'GeomFromText({0})' or just function like 'now()'
     * @param mixed[] ...$values
     * @return self
     */
    public static function of(string $expression, ...$value) : self
    {
        return new static($expression, ...$value);
    }

    /**
     * Compile expression using given placeholder name.
     *
     * @param Driver $driver
     * @param string $placeholder name
     * @return Query
     */
    public function compile(Driver $driver, string $placeholder) : Query
    {
        $placeholder = Strings::startsWith($placeholder, ':') ? $placeholder : ":{$placeholder}" ;
        $expression  = $this->expression;
        $params      = [];
        foreach ($this->values as $key => $value) {
            $new_placeholder          = "{$placeholder}__{$key}";
            $expression               = str_replace("{{$key}}", "{$new_placeholder}", $expression);
            $params[$new_placeholder] = $driver->toPdoType($value);
        }
        return $driver->sql($expression, $params);
    }
}

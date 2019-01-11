<?php
namespace Rebet\Common;

use Rebet\Common\Exception\LogicException;

/**
 * Callback Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Callback
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get the test callback closure.
     *
     * @param mixed $value
     * @return \Closure of function($item) : bool { retrun $item->$key $operator $value; }
     */
    public static function test($key, string $operator, $value) : \Closure
    {
        return function ($item) use ($key, $operator, $value) {
            $retrieved = Reflector::get($item, $key);
            switch ($operator) {
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
                default:
                    throw LogicException::by("Invalid operator {$operator} given.");
            }
        };
    }

    /**
     * Get the compare callback closure.
     *
     * @param mixed $key (default: null)
     * @param bool $invert (default: false)
     * @return \Closure of function($a, $b) : int { ... }
     */
    public static function compare($key = null, bool $invert = false) : \Closure
    {
        $invert = $invert ? -1 : 1 ;
        return function ($a, $b) use ($key, $invert) {
            $a = $key ? (is_callable($key) ? $key($a) : Reflector::get($a, $key)) : $a ;
            $b = $key ? (is_callable($key) ? $key($b) : Reflector::get($b, $key)) : $b ;
            return $a === $b ? 0 : ($a > $b ? 1 : -1) * $invert;
        };
    }

    /**
     * Get the value retriever callback closure.
     * Note: If you want to use the key name same as php function, you can use the key name with '@' prefix.
     *
     * @param callable|string|null $retriever key name (with/without '@') or function($value):mixed.
     * @return \Closure of function($value) : mixed { ... }
     */
    public static function retriever($retriever) : \Closure
    {
        if (is_callable($retriever)) {
            return \Closure::fromCallable($retriever);
        }

        return function ($item) use ($retriever) {
            return Reflector::get($item, Strings::ltrim($retriever, '@', 1));
        };
    }
}

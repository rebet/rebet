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
     * @param mixed $key
     * @return \Closure of function($l, $r) : int { retrun $l->$key compare $r->$key; }
     */
    public static function compare($key) : \Closure
    {
        return function ($l, $r) use ($key) {
            $l = Reflector::get($l, $key);
            $r = Reflector::get($r, $key);
            return $l === $r ? 0 : ($l > $r ? 1 : -1);
        };
    }
}

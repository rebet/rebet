<?php
namespace Rebet\Tools;

use stdClass;

/**
 * Utils Class
 *
 * It is a class that collects simple utility methods not classified as various specialization utilities.
 * Methods defined in this class may be relocated to specialized classes etc. in the future.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Utils
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * It checks that the given values are equivalent.
     *
     * @param mixed $value
     * @param mixed $other
     * @param \Closure|null $comparator (default: null)
     * @return boolean
     */
    public static function equivalent($value, $other, ?\Closure $comparator = null) : bool
    {
        if ((is_iterable($value) || $value instanceof stdClass) && (is_iterable($other) || $other instanceof stdClass)) {
            foreach ($value as $k => $v) {
                if (!static::equivalent($v, Reflector::get($other, $k))) {
                    return false;
                }
            }

            return true;
        }

        if ($comparator) {
            return $comparator($value, $other);
        }

        return $value === Reflector::convert($other, Reflector::getType($value)) || Reflector::convert($value, Reflector::getType($other)) === $other;
    }

    /**
     * Method version of ternary operation.
     *
     * ex)
     * Utils::when(1 === 1, 'yes', 'no'); //=> 'yes'
     *
     * @param mixed $expr
     * @param mixed $ifTrue
     * @param mixed $ifFalse
     * @return mixed
     */
    public static function when($expr, $ifTrue, $ifFalse)
    {
        return $expr ? $ifTrue : $ifFalse ;
    }

    /**
     * Get the first element that is not blank.
     *
     * ex)
     * Utils::coalesce(null, [], '', 3, 'a');    //=> 3
     * Utils::coalesce(null, [], '', 0, 3, 'a'); //=> 0
     *
     * @param mixed ...$items
     * @return mixed
     */
    public static function coalesce(...$items)
    {
        foreach ($items as $item) {
            if (!static::isBlank($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * It checks whether the given value is blank.
     * Note: blank is null, ''(empty string) or [](empty array)
     *
     * ex)
     *  - null    => true
     *  - false   => false
     *  - 'false' => false
     *  - 0       => false
     *  - '0'     => false
     *  - ''      => true
     *  - []      => true
     *  - [null]  => false
     *  - [1]     => false
     *  - 'abc'   => false
     *
     * @param  mixed $value
     * @return bool
     */
    public static function isBlank($value) : bool
    {
        return $value === null || $value === '' || $value === [] ;
    }

    /**
     * If the given value is blank, it returns the default value.
     *
     * @param  mixed $value
     * @param  mixed $default
     * @return mixed
     * @see static::isBlank()
     */
    public static function bvl($value, $default)
    {
        return static::isBlank($value) ? $default : $value ;
    }

    /**
     * It checks whether the given value is empty.
     * Note: empty is null, 0, ''(empty string) or [](empty array)
     *
     * ex)
     *  - null    => true
     *  - false   => false
     *  - 'false' => false
     *  - 0       => true
     *  - '0'     => false
     *  - ''      => true
     *  - []      => true
     *  - [null]  => false
     *  - [1]     => false
     *  - 'abc'   => false
     *
     * @param  mixed $value
     * @return bool
     */
    public static function isEmpty($value) : bool
    {
        return $value === null || $value === '' || $value === [] || $value === 0 ;
    }

    /**
     * If the given value is empty, it returns the default value.
     *
     * @param  mixed $value
     * @param  mixed $default
     * @return mixed
     * @see static::isEmpty()
     */
    public static function evl($value, $default)
    {
        return static::isEmpty($value) ? $default : $value ;
    }

    /**
     * Get an anonymous function for embedding a string into a here-document.
     *
     * ex)
     * $_ = Utils::heredocImplanter();
     * $str = <<<EOS
     *     text text text {$_(Class::CONST)}
     *     {$_(CONSTANT)} text
     * EOS;
     *
     * @return \Closure function($value) : mixed
     */
    public static function heredocImplanter() : \Closure
    {
        return Callback::echoBack();
    }

    /**
     * Convert to int type
     * Note: null / empty string returns null.
     *
     * @param mixed $var
     * @param int $base
     * @return int|null
     */
    public static function intval($var, int $base = null) : ?int
    {
        return $var === null || $var === '' ? null : intval($var, $base);
    }

    /**
     * Convert to float type
     * Note: null / empty string returns null.
     *
     * @param mixed $var
     * @return float|null
     */
    public static function floatval($var) : ?float
    {
        return $var === null || $var === '' ? null : floatval($var);
    }
}

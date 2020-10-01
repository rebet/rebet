<?php
namespace Rebet\Tools;

use Rebet\Tools\Exception\LogicException;

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
     * @var array
     */
    protected static $cache = [];

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
                    throw new LogicException("Invalid operator {$operator} given.");
            }
        };
    }

    /**
     * Get the compare callback closure.
     *
     * @param string|callabel|null $key of string or function($value):mixed (default: null)
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

    /**
     * Create a string of given callable.
     *
     * @param callable $callback
     * @param bool $verbose (default: true)
     * @return string
     */
    public static function stringify(callable $callback, bool $verbose = true) : string
    {
        $reflector = new \ReflectionFunction(\Closure::fromCallable($callback));
        $class     = $reflector->getClosureScopeClass();
        $function  = $class ? $class->getShortName().'::'.$reflector->getShortName() : $reflector->getShortName() ;
        if ($verbose && $class) {
            $function = $class->getNamespaceName().'\\'.$function;
        }

        $string     = "{$function}(";
        $parameters = $reflector->getParameters();
        foreach ($parameters as $parameter) {
            $type_hint = null;
            $name      = '$'.$parameter->getName();
            $name      = $parameter->isVariadic() ? "...{$name}" : $name ;
            $name      = $parameter->isPassedByReference() ? "&{$name}" : $name ;

            if ($verbose) {
                $type_hint = Reflector::getTypeHint($parameter);
                $type_hint = $type_hint !== null && $parameter->allowsNull() ? "?{$type_hint}" : $type_hint ;

                try {
                    $name = $parameter->isOptional() ? "{$name} = ".(Strings::rbtrim($parameter->getDefaultValueConstantName(), '\\') ?? $parameter->getDefaultValue() ?? 'null') : $name ;
                } catch (\ReflectionException $e) {
                    // It is not possible to get the default value of built-in functions or methods of built-in classes.
                    // Trying to do this will result a ReflectionException being thrown.
                }
            }

            $string .= $type_hint === null ? "{$name}, " : "{$type_hint} {$name}, " ;
        }
        if (!empty($parameters)) {
            $string  = Strings::rcut($string, 2);
        }
        $string .= ')';

        if ($verbose) {
            if ($return_type = $reflector->getReturnType()) {
                $type_name = $return_type->getName() ;
                $type_name = $return_type->allowsNull() ? "?{$type_name}" : $type_name ;
                $string    = "{$string} : {$type_name}";
            }
        }

        return $string;
    }

    /**
     * Return the echo back closure.
     *
     * @return \Closure
     */
    public static function echoBack() : \Closure
    {
        return static::$cache[__FUNCTION__] ?? static::$cache[__FUNCTION__] = function ($value) { return $value; };
    }

    /**
     * Return the string length comparator.
     *
     * @return \Closure
     */
    public static function compareLength() : \Closure
    {
        return static::$cache[__FUNCTION__] ?? static::$cache[__FUNCTION__] = Callback::compare(function ($key) { return $key ? mb_strlen($key) : 0 ; });
    }
}

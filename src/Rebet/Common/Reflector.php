<?php
namespace Rebet\Common;

/**
 * Reflector Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Reflector
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Instantiate based on the definition object.
     * The definition objects that instance creation can deal with are as follows.
     *
     *  string :
     *     {ClassName}@{factoryMathod}
     *       => Instantiate the target class with a factory method without arguments
     *     {ClassName}
     *       => Instantiate the target class with a constructor without arguments
     *
     *  array :
     *     [{ClassName}@{factoryMathod}, arg1, arg2, ... ]
     *       ⇒ Instantiate the target class with a factory method with arguments
     *     [{ClassName}, arg1, arg2, ... ]
     *       ⇒ Instantiate the target class with a constructor with arguments
     *
     *  brank : (= null, '', [])
     *       ⇒ return null
     *
     *  other : (= already instantiated)
     *       ⇒ return input value
     *
     * @param mixed $config
     * @return mixed
     */
    public static function instantiate($config)
    {
        if (Utils::isBlank($config)) {
            return null;
        }
        if (is_string($config)) {
            [$class, $method] = Strings::split($config, '@', 2);
            return empty($method) ? new $class() : $class::$method() ;
        }
        if (is_array($config)) {
            $class_config     = array_shift($config);
            [$class, $method] = Strings::split($class_config, '@', 2);
            return empty($method) ? new $class(...$config) : $class::$method(...$config) ;
        }
        return $config;
    }

    /**
     * Get value from an array or object using "dot" notation.
     *
     * ex)
     * Reflector::get($user, 'name');
     * Reflector::get($user, 'bank.name');
     * Reflector::get($user, 'shipping_address.0', $user->address);
     * Reflector::get($_REQUEST, 'opt_in', false);
     *
     * @todo Wiled card access like 'array.*.key' support
     *
     * @param  array|object|null $object
     * @param  int|string $key You can use dot notation
     * @param  mixed $default (default: null)
     * @param  bool $accessible (default: false)
     * @return mixed
     *
     * @see DotAccessDelegator
     */
    public static function get($object, $key, $default = null, bool $accessible = false)
    {
        while ($object instanceof DotAccessDelegator) {
            $object = $object->get();
        }
        if ($object === null) {
            return $default;
        }
        if (Utils::isBlank($key)) {
            return $object === null ? $default : static::resolveDotAccessDelegator($object) ;
        }
        
        if (Arrays::accessible($object) && Arrays::exists($object, $key)) {
            return static::resolveDotAccessDelegator($object[$key]) ?? $default ;
        }

        $current = Strings::latrim($key, '.');
        if ($current != $key) {
            $target = static::get($object, $current, null, $accessible);
            if ($target === null) {
                return $default;
            }
            return static::get($target, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $default, $accessible);
        }

        if (Arrays::accessible($object)) {
            if (!isset($object[$current])) {
                return $default;
            }
            $value = $object[$current];
            return $value === null ? $default : static::resolveDotAccessDelegator($value) ;
        }

        if (!property_exists($object, $current)) {
            return $default;
        }
        $rp = new \ReflectionProperty($object, $current);
        if (!$accessible && !$rp->isPublic()) {
            return $default;
        }
        $rp->setAccessible($accessible);
        $value = $rp->getValue($object);
        return $value === null ? $default : static::resolveDotAccessDelegator($value) ;
    }
    
    /**
     * Resolve DotAccessDelegator.
     *
     * @param mixed $object
     * @return mixed
     */
    private static function resolveDotAccessDelegator($object)
    {
        while ($object instanceof DotAccessDelegator) {
            $object = $object->get();
        }

        if (
            $object === null ||
            \is_scalar($object) ||
            \is_resource($object) ||
            (is_iterable($object) && !is_array($object) && !($object instanceof \ArrayAccess))
        ) {
            return $object;
        }

        foreach ($object as $key => $value) {
            static::set($object, $key, static::resolveDotAccessDelegator($value), true);
        }

        return $object;
    }

    /**
     * Set a value to an array or object.
     *
     * Please be aware that if you set a value with this method,
     * the DotAccessDelegator structure of the target object data will be lost.
     *
     * ex)
     * Reflector::set($user, 'name', 'new name');
     * Reflector::set($user, 'bank.name', 'new bank');
     * Reflector::set($user, 'shipping_address.0', $user->address);
     * Reflector::set($_REQUEST, 'opt_in', false);
     *
     * @param  array|object $object
     * @param  int|string $key You can use dot notation
     * @param  mixed $value
     * @param  bool $accessible (default: false) ... Valid only for objects
     * @return mixed 値
     * @throws \OutOfBoundsException
     */
    public static function set(&$object, $key, $value, bool $accessible = false) : void
    {
        while ($object instanceof DotAccessDelegator) {
            $object = $object->get();
        }
        if (Arrays::accessible($object) && ($key === null || Arrays::exists($object, $key))) {
            if ($key === null) {
                $object[] = $value;
            } else {
                $object[$key] = $value;
            }
            return;
        }
        $current = Strings::latrim($key, '.');
        if (Arrays::accessible($object)) {
            if ($current != $key) {
                if (!\array_key_exists($current, $object)) {
                    throw new \OutOfBoundsException("Nested parent key '{$current}' does not exist.");
                }
                static::set($object[$current], \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value, $accessible);
            } else {
                $object[$current] = $value;
            }
            return;
        }

        if (!\property_exists($object, $current)) {
            throw new \OutOfBoundsException("Nested key '{$current}' does not exist.");
        }
        if ($current != $key) {
            $rp = new \ReflectionProperty($object, $current);
            $rp->setAccessible($rp->getModifiers() === 4096 ? true : $accessible);
            $target = $rp->getValue($object);
            static::set($target, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value, $accessible);
            $rp->setValue($object, $target);
        } else {
            $rp = new \ReflectionProperty($object, $current);
            $rp->setAccessible($rp->getModifiers() === 4096 ? true : $accessible);
            $rp->setValue($object, $value);
        }
        return;
    }
    
    /**
     * It checks whether an array or object has a given property.
     *
     * ex)
     * Reflector::has($user, 'name');
     * Reflector::has($user, 'bank.name');
     * Reflector::has($user, 'shipping_address.0');
     * Reflector::has($_REQUEST, 'opt_in');
     *
     * @param  array|object|null $object
     * @param  int|string $key You can use dot notation
     * @param  bool $accessible
     * @return bool
     */
    public static function has($object, $key, bool $accessible = false)
    {
        while ($object instanceof DotAccessDelegator) {
            $object = $object->get();
        }
        if ($object === null) {
            return false;
        }
        
        $current  = Strings::latrim($key, '.');
        $nest_obj = null;
        if (Arrays::accessible($object)) {
            if (!Arrays::exists($object, $current)) {
                return false;
            }
            $nest_obj = $object[$current];
        } else {
            if (!property_exists($object, $current)) {
                return false;
            }
            $rp = new \ReflectionProperty($object, $current);
            if (!$accessible && !$rp->isPublic()) {
                return false;
            }
            $rp->setAccessible($accessible);
            $nest_obj = $rp->getValue($object);
            // $nest_obj = $object->{$current};
        }
        while ($nest_obj instanceof DotAccessDelegator) {
            $nest_obj = $nest_obj->get();
        }

        return $current == $key ? true : static::has($nest_obj, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $accessible);
    }

    /**
     * Remove a key/property from an array or object (only public properties).
     * When applying this method to an object property, the end property of dot notation must be public.
     *
     * ex)
     * Reflector::remove($user, 'name');
     * Reflector::remove($user, 'bank.name');
     * Reflector::remove($user, 'shipping_address.0');
     * Reflector::remove($_REQUEST, 'opt_in');
     *
     * @param  array|object $object
     * @param  int|string $key You can use dot notation
     * @param  bool $accessible (default: false) ... Valid only for objects
     * @return mixed removed value
     * @throws \OutOfBoundsException
     */
    public static function remove(&$object, $key, bool $accessible = false)
    {
        while ($object instanceof DotAccessDelegator) {
            $object = $object->get();
        }
        if (Arrays::accessible($object) && Arrays::exists($object, $key)) {
            $ret = $object[$key];
            unset($object[$key]);
            return static::resolveDotAccessDelegator($ret);
        }
        $current = Strings::latrim($key, '.');
        if (Arrays::accessible($object)) {
            if ($current != $key) {
                if (!\array_key_exists($current, $object)) {
                    throw new \OutOfBoundsException("Nested parent key '{$current}' does not exist.");
                }
                return static::remove($object[$current], \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $accessible);
            } else {
                $ret = $object[$current] ?? null;
                unset($object[$current]);
                return static::resolveDotAccessDelegator($ret);
            }
        }

        if (!\property_exists($object, $current)) {
            throw new \OutOfBoundsException("Nested key '{$current}' does not exist.");
        }
        if ($current != $key) {
            $rp = new \ReflectionProperty($object, $current);
            $rp->setAccessible($rp->getModifiers() === 4096 ? true : $accessible);
            $target = $rp->getValue($object);
            $ret    = static::remove($target, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $accessible);
            $rp->setValue($object, $target);
            return static::resolveDotAccessDelegator($ret);
        } else {
            $rp = new \ReflectionProperty($object, $current);
            if ($rp->isPublic() || $rp->getModifiers() === 4096) {
                $ret = $object->$current;
                unset($object->$current);
                return static::resolveDotAccessDelegator($ret);
            } else {
                throw new \OutOfBoundsException("Nested key '{$current}' can not access.");
            }
        }
        return null;
    }

    /**
     * Converts the data type of the given object.
     * This method attempts type conversion by the following procedure.
     * If conversion is impossible, null is returned.
     *
     *   $converted = Reflector::convert($value, $type);
     *
     * 　1. When $type is null:
     *      -> return $value
     *
     * 　2. When $value is null:
     *      -> return null
     *
     * 　3. When $type is 'array':
     *      -> @see Rebet\Common\Arrays::toArray()
     *
     * 　4. When $type is 'string':
     *      -> If $value is string then return $value (no convert)
     *      -> If $value is resource then return null
     *      -> If $value is scalar then return type casted value
     *      -> If $value has __toSring() method then invoke that
     *      -> If $value is array or ArrayAccess then return json encoded value
     *      -> If $value is object and instanceof JsonSerializable then call jsonSerialize()
     *         -> $And then if $serialize is scalar then return (string)$value
     *         -> $And then if $serialize is array or ArrayAccess then return json encoded value
     *      -> Otherwise return null
     *
     * 　6. When $type is \Closure:
     *      -> If $value is object and instanceof \Closure then return $value (no convert)
     *      -> If $value is callable then call \Closure::fromCallable()
     *      -> Otherwise return null
     *
     * 　7. When $type is scaler(int|float|bool):
     *      -> If $value is_{$type}() then return $value
     *      -> If $value is scaler then invoke {$type}val($value)
     *      -> If $value has convertTo($type) method then invoke that and check return value type
     *      -> If $value has to{$type<without namespace>}() method then invoke that and check return value type
     *      -> Otherwise return null
     *
     *   8. When $type is object:
     *      -> If $type has valueOf($value) static method then invoke that and check return value type
     *      -> If $type has of($value) static method then invoke that and check return value type
     *      -> If $value has convertTo($type) method then invoke that and check return value type
     *      -> If $value has to{$type<without namespace>}() method then invoke that and check return value type
     *      -> Otherwise return null
     *
     * @see Convertible
     *
     * @param mixed $value
     * @param string|null $type
     * @return mixed
     */
    public static function convert($value, ?string $type)
    {
        if ($type === null) {
            return $value;
        }
        if ($value === null) {
            return null;
        }
        if (static::typeOf($value, $type)) {
            return $value;
        }

        switch ($type) {
            //---------------------------------------------
            // To Array
            //---------------------------------------------
            case 'array':
                return Arrays::toArray($value);

            //---------------------------------------------
            // To String
            //---------------------------------------------
            case 'string':
                if (is_string($value)) {
                    return $value;
                }
                if (is_resource($value)) {
                    return null;
                }
                if (is_scalar($value)) {
                    return (string)$value;
                }
                if (method_exists($value, '__toString')) {
                    return $value->__toString();
                }
                if (Arrays::accessible($value)) {
                    return json_encode(Arrays::toArray($value));
                }
                if (is_object($value) && $value instanceof \JsonSerializable) {
                    $json = $value->jsonSerialize();
                    if (is_scalar($json)) {
                        return (string)$json;
                    }
                    if (Arrays::accessible($json)) {
                        return json_encode(Arrays::toArray($json));
                    }
                }
                return null;

            //---------------------------------------------
            // To Callable
            //---------------------------------------------
            case 'callable':
                if (is_callable($value)) {
                    return $value;
                }
                return null;

            //---------------------------------------------
            // To Closure
            //---------------------------------------------
            case \Closure::class:
                if ($value instanceof \Closure) {
                    return $value;
                }
                if (is_callable($value)) {
                    return \Closure::fromCallable($value);
                }
                return null;

            //---------------------------------------------
            // To Scalar (int|float|bool)
            //---------------------------------------------
            case 'int':
            case 'float':
            case 'bool':
                if (static::typeOf($value, $type)) {
                    return $value;
                }
                if (is_scalar($value)) {
                    $convertor = "{$type}val";
                    return $convertor($value);
                }
                return
                    static::tryConvertByMember($value, 'convertTo', $type) ??
                    static::tryConvertByMember($value, "to".ucfirst($type), $type)
                ;

            //---------------------------------------------
            // To Object
            //---------------------------------------------
            default:
                $rc = new \ReflectionClass($type);
                return
                    static::tryConvertByStatic($type, 'valueOf', $value) ??
                    static::tryConvertByStatic($type, 'of', $value) ??
                    static::tryConvertByMember($value, 'convertTo', $type) ??
                    static::tryConvertByMember($value, "to".$rc->getShortName(), $type)
                ;
        }
    }

    /**
     * Try to convert another type using static method of given type.
     *
     * @param string $type
     * @param string $method
     * @param mixed $value
     * @return mixed
     */
    protected static function tryConvertByStatic(string $type, string $method, $value)
    {
        if (method_exists($type, $method)) {
            $converted = $type::$method($value);
            if (static::typeOf($converted, $type)) {
                return $converted;
            }
        }
        return null;
    }

    /**
     * Try to convert another type using member method of given object.
     *
     * @param mixed $value
     * @param string $method
     * @param string $type
     * @return mixed
     */
    protected static function tryConvertByMember($value, string $method, string $type)
    {
        if (method_exists($value, $method)) {
            $rm        = new \ReflectionMethod($value, $method);
            $converted = $rm->getNumberOfParameters() === 0 ? $value->$method() : $value->$method($type);
            if (static::typeOf($converted, $type)) {
                return $converted;
            }
        }
        return null;
    }
    
    /**
     * It checks whether the target value is the given Type
     * # If value is null then return false
     * # If type is null then return true
     *
     * @param mixed $value
     * @param string|null $type type or class
     * @return boolean
     */
    public static function typeOf($value, ?string $type) : bool
    {
        if ($type === null) {
            return true;
        }
        if ($value === null) {
            return false;
        }
        if (is_object($value) && $value instanceof $type) {
            return true;
        } else {
            $type_check = "is_{$type}";
            if (function_exists($type_check) && $type_check($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get type or class name given value.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function getType($value) : ?string
    {
        if($value === null) {
            return null;
        }
        if(is_object($value)) {
            return get_class($value);
        }
        foreach (['string', 'array', 'int', 'float', 'bool', 'callable', 'resource', 'iterable'] as $type) {
            $type_check = "is_{$type}";
            if($type_check($value)) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Get type or class name which is a type hint as a character string.
     * # If type hint is nothing then return null.
     *
     * @param \ReflectionParameter|null $param
     * @return string|null
     */
    public static function getTypeHint(?\ReflectionParameter $param) : ?string
    {
        if ($param === null) {
            return null;
        }
        $type = $param->getType();
        if (!empty($type)) {
            return (string)$type;
        }

        $type = $param->getClass();
        if (!empty($type)) {
            return $type->getName();
        }

        return null;
    }

    /**
     * Invoke a method given object
     *
     * @param string|object $object
     * @param string $method
     * @param array $args
     * @param boolean $accessible
     * @return mixed
     */
    public static function invoke($object, string $method, array $args = [], bool $accessible = false)
    {
        $method = new \ReflectionMethod($object, $method);
        $method->setAccessible($accessible);
        return $method->invoke(is_object($object) ? $object : null, ...$args);
    }

    /**
     * It checks the class or object uses given trait.
     *
     * @param object|string $class
     * @param string $trait
     * @return boolean
     */
    public static function uses($target, string $trait) : bool
    {
        $classes = array_merge([$target], class_parents($target));
        foreach ($classes as $class) {
            if (in_array($trait, class_uses($class), true)) {
                return true;
            }
        }
        return false;
    }
}

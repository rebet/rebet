<?php
namespace Rebet\Common;

use Rebet\Common\Strings;
use Rebet\Common\Utils;

/**
 * リフレクター クラス
 *
 * 各種リフレクション関連の処理を行います。
 *
 * $user_name = Reflector::get($_REQUEST, 'user.name');
 * if(Utils::isBlank($user_name)) {
 *     // something to do
 * }
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Reflector
{
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 定義オブジェクトを元にインスタンス生成を行います。
     * なお、インスタンス生成が対応可能な定義オブジェクトは下記の通りです。
     *
     *  string :
     *     {ClassName}@{factoryMathod}形式
     *       ⇒ 対象クラスを引数無しのファクトリメソッドでインスタンス化します
     *     {ClassName}形式
     *       ⇒ 対象クラスを引数無しのコンストラクタでインスタンス化します
     *  array :
     *     [{ClassName}@{factoryMathod}, arg1, arg2, ... ]形式
     *       ⇒ 対象クラスを引数付きのファクトリメソッドでインスタンス化します
     *     [{ClassName}, arg1, arg2, ... ]形式
     *       ⇒ 対象クラスを引数付きのコンストラクタでインスタンス化します
     *     [callable, arg1, arg2, ... ]形式
     *       ⇒ callable(arg1, arg2, ...) でインスタンス化します
     *  brank : (= null, '', [])
     *       ⇒ null を返します
     *  other :
     *       ⇒ そのまま値を返します
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
            [$class, $method] = array_pad(\explode('@', $config), 2, null);
            return empty($method) ? new $class() : $class::$method() ;
        }
        if (is_array($config)) {
            $class_config = array_shift($config);
            if (\is_callable($class_config)) {
                return $class_config(...$config);
            }
            [$class, $method] = array_pad(\explode('@', $class_config), 2, null);
            return empty($method) ? new $class(...$config) : $class::$method(...$config) ;
        }
        return $config;
    }

    /**
     * 配列又はオブジェクトから値を取得します。
     *
     * ex)
     * Reflector::get($user, 'name');
     * Reflector::get($user, 'bank.name');
     * Reflector::get($user, 'shipping_address.0', $user->address);
     * Reflector::get($_REQUEST, 'opt_in', false);
     *
     * @param  array|object|null $object 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @param  mixed $default デフォルト値
     * @return mixed 値
     */
    public static function get($object, $key, $default = null)
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

        $current = Strings::latrim($key, '.');
        if ($current != $key) {
            $target = static::get($object, $current);
            if ($target === null) {
                return $default;
            }
            return static::get($target, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $default);
        }

        if (is_array($object)) {
            if (!isset($object[$current])) {
                return $default;
            }
            $value = $object[$current];
            return $value === null ? $default : static::resolveDotAccessDelegator($value) ;
        }

        if (!property_exists($object, $current)) {
            return $default;
        }
        $value = $object->{$current};
        return $value === null ? $default : static::resolveDotAccessDelegator($value) ;
    }
    
    /**
     * DotAccessDelegator を解決します。
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
            static::set($object, $key, static::resolveDotAccessDelegator($value));
        }

        return $object;
    }

    /**
     * 配列又はオブジェクトに値を設定します。
     *
     * なお、本メソッドにて値を設定した場合、対象オブジェクトデータの DotAccessDelegator 構造
     * が失われますのでご注意ください。
     *
     * ex)
     * Reflector::set($user, 'name', 'new name');
     * Reflector::set($user, 'bank.name', 'new bank');
     * Reflector::set($user, 'shipping_address.0', $user->address);
     * Reflector::set($_REQUEST, 'opt_in', false);
     *
     * @param  array|object $object 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @param  mixed $value 設定値
     * @return mixed 値
     * @throws \OutOfBoundsException
     */
    public static function set(&$object, $key, $value) : void
    {
        while ($object instanceof DotAccessDelegator) {
            $object = $object->get();
        }
        $current = Strings::latrim($key, '.');
        if (is_array($object)) {
            if (!\array_key_exists($current, $object)) {
                throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
            }
            if ($current != $key) {
                static::set($object[$current], \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
            } else {
                $object[$current] = $value;
            }
            return;
        }

        if (!\property_exists($object, $current)) {
            throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
        }
        if ($current != $key) {
            static::set($object->$current, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
        } else {
            $object->$current = $value;
        }
        return;
    }
    
    /**
     * 配列又はオブジェクトが指定プロパティを持つかチェックします。
     *
     * ex)
     * Reflector::has($user, 'name');
     * Reflector::has($user, 'bank.name');
     * Reflector::has($user, 'shipping_address.0');
     * Reflector::has($_REQUEST, 'opt_in');
     *
     * @param  array|object|null $object 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @return bool true: 存在する, false: 存在しない
     */
    public static function has($object, $key)
    {
        while ($object instanceof DotAccessDelegator) {
            $object = $object->get();
        }
        if ($object === null) {
            return false;
        }
        
        $current  = Strings::latrim($key, '.');
        $nest_obj = null;
        if (is_array($object)) {
            if (!array_key_exists($current, $object)) {
                return false;
            }
            $nest_obj = $object[$current];
        } else {
            if (!property_exists($object, $current)) {
                return false;
            }
            $nest_obj = $object->{$current};
        }
        while ($nest_obj instanceof DotAccessDelegator) {
            $nest_obj = $nest_obj->get();
        }

        return $current == $key ? true : static::has($nest_obj, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1));
    }

    /**
     * 指定オブジェクトのデータ型を変換します。
     * 本メソッドは以下の手順で型変換を試みます。
     * なお、変換が出来ない場合は null が返ります。
     *
     * 　1. $type が null の場合:
     *      -> $value を返す
     * 　2. $value が null の場合:
     *      -> null を返す
     * 　3. $type が array  の場合:
     *      -> $value が is_array() なら変換なし
     *      -> $value が is_string() なら expload(',', $value)
     *      -> $value::toArray() があれば実行
     *      -> $value instanceof Traversable なら foreach で array 変換
     *      -> $value が object で instanceof JsonSerializable なら jsonSerialize()
     *         -> $json が is_array() なら $json
     *         -> $json が is_array() でなければ [$value]
     *      -> $value が object なら get_object_vars($value)
     *      -> それ以外なら (array)$value
     * 　4. $type が string の場合:
     *      -> $value が is_string() なら変換なし
     *      -> $value が is_resource() なら null
     *      -> $value が is_scalar() なら型キャスト
     *      -> $value が object で instanceof JsonSerializable なら jsonSerialize()
     *         -> $json が is_scalar() なら (string)$value
     *      -> $value::__toSring() が存在すれば実行
     *      -> それ以外なら null
     * 　5. $type が callable の場合:
     *      -> $value が callable なら変換なし
     *      -> それ以外なら null
     * 　6. $type が \Closure の場合:
     *      -> $value が object で instanceof \Closure なら変換なし
     *      -> $value が callable なら \Closure::fromCallable() で変換
     *      -> それ以外なら null
     * 　7. $type が scaler(int|float|bool) の場合:
     *      -> $value が is_{$type}() なら変換なし
     *      -> $value が scaler なら {$type}val($value) を実行
     *      -> $value::convertTo($type) が存在すれば実行 -> 型チェック
     *      -> $value::to{$type<without namespace>}() が存在すれば実行 -> 型チェック
     *      -> それ以外なら null
     *   8. $type が object の場合:
     *      -> $type::valueOf($value) が存在すれば実行 -> 型チェック
     *      -> $value::convertTo($type) が存在すれば実行 -> 型チェック
     *      -> $value::to{$type<without namespace>}() が存在すれば実行 -> 型チェック
     *      -> それ以外なら null
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

        switch ($type) {
            //---------------------------------------------
            // To Array
            //---------------------------------------------
            case 'array':
                if (is_array($value)) {
                    return $value;
                }
                if (is_string($value)) {
                    return explode(',', $value);
                }
                if (method_exists($value, 'toArray')) {
                    return $value->toArray();
                }
                if ($value instanceof \Traversable) {
                    $array = [];
                    foreach ($value as $key => $value) {
                        $array[$key] = $value;
                    }
                    return $array;
                }
                if (is_object($value)) {
                    if ($value instanceof \JsonSerializable) {
                        $json = $value->jsonSerialize();
                        return is_array($json) ? $json : [$value] ;
                    }
                    return get_object_vars($value);
                }
                return (array)$value;

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
                if (is_object($value) && $value instanceof \JsonSerializable) {
                    $json = $value->jsonSerialize();
                    if (is_scalar($json)) {
                        return (string)$json;
                    }
                }
                if (method_exists($value, '__toString')) {
                    return $value->__toString();
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
                    static::tryConvertByMember($value, 'convertTo', $type) ??
                    static::tryConvertByMember($value, "to".$rc->getShortName(), $type)
                ;
        }
    }

    /**
     * 指定タイプの static メソッドを用いて型変換を試みます。
     *
     * @param string $type 変換対象の型
     * @param string $method 利用メソッド名
     * @param mixed $value 変換元の値
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
     * 変換元オブジェクトの member メソッドを用いて型変換を試みます。
     *
     * @param mixed $value 変換元の値
     * @param string $method 利用メソッド名
     * @param string $type 変換対象の型
     * @return mixed
     */
    protected static function tryConvertByMember($value, string $method, string $type)
    {
        if (method_exists($value, $method)) {
            $rm = new \ReflectionMethod($value, $method);
            $converted = $rm->getNumberOfParameters() === 0 ? $value->$method() : $value->$method($type);
            if (static::typeOf($converted, $type)) {
                return $converted;
            }
        }
        return null;
    }
    
    /**
     * 対象の値が指定の Type かチェックします。
     * ※value が null の場合は false を返します。
     * ※type が null の場合は true を返します。
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
     * タイプヒントになっている type 又は クラス名 を文字列で取得します。
     * ※タイプヒントがついていない場合は null が返ります。
     *
     * @param \ReflectionParameter $param
     * @return string|null
     */
    public static function getTypeHint(\ReflectionParameter $param) : ?string
    {
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
}

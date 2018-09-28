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
     *     {ClassName}::{factoryMathod}形式
     *       ⇒ 対象クラスを引数無しのファクトリメソッドでインスタンス化します
     *     {ClassName}形式
     *       ⇒ 対象クラスを引数無しのコンストラクタでインスタンス化します
     *  callable :
     *       ⇒ callable() でインスタンス化します
     *  array :
     *     [{ClassName}::{factoryMathod}, arg1, arg2, ... ]形式
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
            [$class, $method] = array_pad(\explode('::', $config), 2, null);
            return empty($method) ? new $class() : $class::$method() ;
        }
        if (is_callable($config)) {
            return $config();
        }
        if (is_array($config)) {
            $class_config = array_shift($config);
            if (\is_callable($class_config)) {
                return $class_config(...$config);
            }
            [$class, $method] = array_pad(\explode('::', $class_config), 2, null);
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
     * @param  array|object|null $obj 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @param  mixed $default デフォルト値
     * @return mixed 値
     */
    public static function get($obj, $key, $default = null)
    {
        while ($obj instanceof DotAccessDelegator) {
            $obj = $obj->get();
        }
        if ($obj === null) {
            return $default;
        }
        if (Utils::isBlank($key)) {
            return $obj === null ? $default : static::resolveDotAccessDelegator($obj) ;
        }

        $current = Strings::latrim($key, '.');
        if ($current != $key) {
            $target = static::get($obj, $current);
            if ($target === null) {
                return $default;
            }
            return static::get($target, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $default);
        }

        if (is_array($obj)) {
            if (!isset($obj[$current])) {
                return $default;
            }
            $value = $obj[$current];
            return $value === null ? $default : static::resolveDotAccessDelegator($value) ;
        }

        if (!property_exists($obj, $current)) {
            return $default;
        }
        $value = $obj->{$current};
        return $value === null ? $default : static::resolveDotAccessDelegator($value) ;
    }
    
    /**
     * DotAccessDelegator を解決します。
     *
     * @param mixed $obj
     * @return mixed
     */
    private static function resolveDotAccessDelegator($obj)
    {
        while ($obj instanceof DotAccessDelegator) {
            $obj = $obj->get();
        }

        if ($obj === null || \is_scalar($obj) || \is_resource($obj)) {
            return $obj;
        }

        foreach ($obj as $key => $value) {
            static::set($obj, $key, static::resolveDotAccessDelegator($value));
        }

        return $obj;
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
     * @param  array|object $obj 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @param  mixed $value 設定値
     * @return mixed 値
     * @throws \OutOfBoundsException
     */
    public static function set(&$obj, $key, $value) : void
    {
        while ($obj instanceof DotAccessDelegator) {
            $obj = $obj->get();
        }
        $current = Strings::latrim($key, '.');
        if (is_array($obj)) {
            if (!\array_key_exists($current, $obj)) {
                throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
            }
            if ($current != $key) {
                static::set($obj[$current], \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
            } else {
                $obj[$current] = $value;
            }
            return;
        }

        if (!\property_exists($obj, $current)) {
            throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
        }
        if ($current != $key) {
            static::set($obj->$current, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
        } else {
            $obj->$current = $value;
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
     * @param  array|object|null $obj 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @return bool true: 存在する, false: 存在しない
     */
    public static function has($obj, $key)
    {
        while ($obj instanceof DotAccessDelegator) {
            $obj = $obj->get();
        }
        if ($obj === null) {
            return false;
        }
        
        $current  = Strings::latrim($key, '.');
        $nest_obj = null;
        if (is_array($obj)) {
            if (!array_key_exists($current, $obj)) {
                return false;
            }
            $nest_obj = $obj[$current];
        } else {
            if (!property_exists($obj, $current)) {
                return false;
            }
            $nest_obj = $obj->{$current};
        }
        while ($nest_obj instanceof DotAccessDelegator) {
            $nest_obj = $nest_obj->get();
        }

        return $current == $key ? true : static::has($nest_obj, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1));
    }
}

<?php
namespace Rebet\Common;

use Rebet\Common\StringUtil;

/**
 * 汎用 ユーティリティ クラス
 *
 * 各種特化ユーティリティに分類されない簡便なユーティリティメソッドを集めたクラスです。
 * 本クラスに定義されているメソッドは将来的に特化クラスなどへ移設される可能性があります。
 *
 * $user_name = Util::get($_REQUEST, 'user.name');
 * if(Util::isBlank($user_name)) {
 *     // something to do
 * }
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Util
{
    
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 三項演算のメソッド版
     *
     * ex)
     * Util::when(1 === 1, 'yes', 'no'); //=> 'yes'
     *
     * @param mixed $expr 判別式
     * @param mixed $ifTrue 真の場合の値
     * @param mixed $ifFalse 偽の場合の値
     * @return mixed 三項演算の結果
     */
    public static function when($expr, $ifTrue, $ifFalse)
    {
        return $expr ? $ifTrue : $ifFalse ;
    }
    
    /**
     * 空でない最初の要素を返します。
     *
     * ex)
     * Util::coalesce(null, [], '', 0, 3, 'a'); //=> 3
     *
     * @param mixed ...$items 要素
     * @return mixed 空でない最初の要素
     */
    public static function coalesce(...$items)
    {
        foreach ($items as $item) {
            if (!empty($item)) {
                return $item;
            }
        }
        return null;
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
        if (self::isBlank($config)) {
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
     * Util::get($user, 'name');
     * Util::get($user, 'bank.name');
     * Util::get($user, 'shipping_address.0', $user->address);
     * Util::get($_REQUEST, 'opt_in', false);
     *
     * @param  array|object|null $obj 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @param  mixed $default デフォルト値
     * @return mixed 値
     */
    public static function get($obj, $key, $default = null)
    {
        while ($obj instanceof TransparentlyDotAccessible) {
            $obj = $obj->get();
        }
        if ($obj === null) {
            return $default;
        }

        $current = StringUtil::latrim($key, '.');
        if ($current != $key) {
            $target = self::get($obj, $current);
            if ($target === null) {
                return $default;
            }
            return self::get($target, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $default);
        }

        if (is_array($obj)) {
            if (!isset($obj[$current])) {
                return $default;
            }
            $value = $obj[$current];
            while ($value instanceof TransparentlyDotAccessible) {
                $value = $value->get();
            }
            return $value ?? $default ;
        }

        if (!property_exists($obj, $current)) {
            return $default;
        }
        $value = $obj->{$current};
        while ($value instanceof TransparentlyDotAccessible) {
            $value = $value->get();
        }
        return $value ?? $default ;
    }
    
    /**
     * 配列又はオブジェクトに値を設定します。
     *
     * なお、本メソッドにて値を設定した場合、対象オブジェクトデータの TransparentlyDotAccessible 構造
     * が失われますのでご注意ください。
     *
     * ex)
     * Util::set($user, 'name', 'new name');
     * Util::set($user, 'bank.name', 'new bank');
     * Util::set($user, 'shipping_address.0', $user->address);
     * Util::set($_REQUEST, 'opt_in', false);
     *
     * @param  array|object $obj 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @param  mixed $value 設定値
     * @return mixed 値
     * @throws \OutOfBoundsException
     */
    public static function set(&$obj, $key, $value) : void
    {
        while ($obj instanceof TransparentlyDotAccessible) {
            $obj = $obj->get();
        }
        $current = StringUtil::latrim($key, '.');
        if (is_array($obj)) {
            if (!\array_key_exists($current, $obj)) {
                throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
            }
            if ($current != $key) {
                self::set($obj[$current], \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
            } else {
                $obj[$current] = $value;
            }
            return;
        }

        if (!\property_exists($obj, $current)) {
            throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
        }
        if ($current != $key) {
            self::set($obj->$current, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
        } else {
            $obj->$current = $value;
        }
        return;
    }
    
    /**
     * 配列又はオブジェクトが指定プロパティを持つかチェックします。
     *
     * ex)
     * Util::has($user, 'name');
     * Util::has($user, 'bank.name');
     * Util::has($user, 'shipping_address.0');
     * Util::has($_REQUEST, 'opt_in');
     *
     * @param  array|object|null $obj 配列 or オブジェクト
     * @param  int|string $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @return bool true: 存在する, false: 存在しない
     */
    public static function has($obj, $key)
    {
        while ($obj instanceof TransparentlyDotAccessible) {
            $obj = $obj->get();
        }
        if ($obj === null) {
            return false;
        }
        
        $current  = StringUtil::latrim($key, '.');
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
        while ($nest_obj instanceof TransparentlyDotAccessible) {
            $nest_obj = $nest_obj->get();
        }

        return $current == $key ? true : self::has($nest_obj, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1));
    }

    /**
     * 対象の値が blank か判定します。
     * ※blank とは null / '' / [] のいずれかです。
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
     * @param  mixed $value 値
     * @return bool treu: blank, false: blank以外
     */
    public static function isBlank($value) : bool
    {
        return $value === null || $value === '' || $value === [] ;
    }
    
    /**
     * 対象の値が blank の場合にデフォルト値を返します。
     *
     * @param  mixed $value 値
     * @param  mixed $default デフォルト値
     * @return mixed 値
     * @see self::isBlank()
     */
    public static function bvl($value, $default)
    {
        return self::isBlank($value) ? $default : $value ;
    }
    
    /**
     * 対象の値が empty か判定します。
     * ※empty とは null / 0 / '' / [] のいずれかです。
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
     * @param  ?mixed $value 値
     * @return bool treu: empty, false: empty以外
     */
    public static function isEmpty($value) : bool
    {
        return $value === null || $value === '' || $value === [] || $value === 0 ;
    }
    
    /**
     * 対象の値が empty の場合にデフォルト値を返します。
     *
     * @param  mixed $value 値
     * @param  mixed $default デフォルト値
     * @return mixed 値
     * @see self::isEmpty()
     */
    public static function evl($value, $default)
    {
        return self::isEmpty($value) ? $default : $value ;
    }
    
    /**
     * ヒアドキュメントへの文字列埋め込み用の匿名関数を返します。
     *
     * ex)
     * $_ = Util::heredocImplanter();
     * $str = <<<EOS
     *     text text text {$_(Class::CONST)}
     *     {$_(CONSTANT)} text
     * EOS;
     *
     * @return \Closure 文字列埋め込み用匿名関数
     */
    public static function heredocImplanter() : \Closure
    {
        return function ($s) {
            return $s;
        };
    }

    /**
     * int 型に変換します
     * ※ null/空文字 は null が返ります
     *
     * @param mixed $var 変換対象
     * @param int $base 基数
     * @return int|null 変換した値
     */
    public static function intval($var, int $base = null) : ?int
    {
        return $var === null || $var === '' ? null : intval($var, $base);
    }
    
    /**
     * float 型に変換します
     * ※ null/空文字 は null が返ります
     *
     * @param mixed $var 変換対象
     * @return float|null 変換した値
     */
    public static function floatval($var) : ?float
    {
        return $var === null || $var === '' ? null : floatval($var);
    }
}

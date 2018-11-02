<?php
namespace Rebet\Common;

/**
 * 汎用 ユーティリティ クラス
 *
 * 各種特化ユーティリティに分類されない簡便なユーティリティメソッドを集めたクラスです。
 * 本クラスに定義されているメソッドは将来的に特化クラスなどへ移設される可能性があります。
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
class Utils
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * 三項演算のメソッド版
     *
     * ex)
     * Utils::when(1 === 1, 'yes', 'no'); //=> 'yes'
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
     * Utils::coalesce(null, [], '', 0, 3, 'a'); //=> 3
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
     * @see static::isBlank()
     */
    public static function bvl($value, $default)
    {
        return static::isBlank($value) ? $default : $value ;
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
     * @see static::isEmpty()
     */
    public static function evl($value, $default)
    {
        return static::isEmpty($value) ? $default : $value ;
    }
    
    /**
     * ヒアドキュメントへの文字列埋め込み用の匿名関数を返します。
     *
     * ex)
     * $_ = Utils::heredocImplanter();
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

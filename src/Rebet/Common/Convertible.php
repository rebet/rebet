<?php
namespace Rebet\Common;

/**
 * 型変換 インターフェース
 *
 * Reflector::convert() による型変換が可能であることを "明示" するインターフェースです。
 * なお、Reflector::convert($value, $type) は メソッドの存在有無を判定して動作するため、
 * 必ずしも本インターフェースを実装している必要はありません。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Convertible
{
    /**
     * 型変換を行います。
     * 変換できない場合は null が返ります
     *
     * @param mixed $value
     * @return mixed
     */
    public static function valueOf($value);
    
    /**
     * 型変換を行います
     * 変換できない場合は null が返ります
     *
     * @param string $type
     * @return mixed
     */
    public function convertTo(string $type);
}

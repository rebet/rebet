<?php
namespace Rebet\Common;

/**
 * システム クラス
 *
 * exit / die などの言語構造や、header などの SAPI でのみ動作する機能群などについて、
 * ユニットテスト時にフック出来るようにすることを目的としたクラスです。
 *
 * 本クラスはユニットテストの際にモッククラスに置換えられます。
 * @see tests/mocks.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class System
{
    
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * exit() のラッパーメソッドです。
     *
     * @param int|string|null ステータス
     */
    public static function exit($status = null) : void
    {
        if ($status === null) {
            exit();
        }
        exit($status);
    }

    /**
     * die() のラッパーメソッドです。
     *
     * @param int|string|null ステータス
     */
    public static function die($status = null) : void
    {
        if ($status === null) {
            die();
        }
        die($status);
    }

    /**
     * header() のラッパーメソッドです。
     *
     * @param string $header ヘッダ文字列
     * @param bool $replace 上書き（デフォルト：true）
     * @param int $http_response_code HTTPレスポンスコード（デフォルト：null）
     */
    public static function header(string $header, bool $replace = true, int $http_response_code = null) : void
    {
        \header($header, $replace, $http_response_code);
    }

    /**
     * headers_list() のラッパーメソッドです。
     */
    public static function headers_list() : array
    {
        return \headers_list();
    }
}

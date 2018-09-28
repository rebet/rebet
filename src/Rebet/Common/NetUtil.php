<?php
namespace Rebet\Common;

/**
 * ネット関連 ユーティリティ クラス
 *
 * ネットワーク関連の簡便なユーティリティメソッドを集めたクラスです。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Nets
{
    
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * バイナリデータをURLに利用可能な文字列に変換します。
     * ※Base64 の URL Unsafe な文字「+/=」を URL Safe な文字「._-」に置換えた文字列を返します。
     *
     * @param mixed $byte バイナリデータ
     * @return string URL利用可能文字列
     */
    public static function encodeBase64Url($byte) : string
    {
        return strtr(base64_encode($byte), '+/=', '._-');
    }
    
    /**
     * URLに利用可能な文字列をバイナリデータに変換します。
     * ※Base64 の URL Unsafe な文字「+/=」を URL Safe な文字「._-」に置換えた文字列からデータを復元します。
     *
     * @param string $encoded 文字列
     * @return mixed バイナリデータ
     */
    public static function decodeBase64Url(string $encoded)
    {
        return base64_decode(strtr($encoded, '._-', '+/='));
    }
    
    /**
     * ページをリダイレクトします。
     * ※本メソッドは exit しません。
     *
     * @param string $url リダイレクトURL
     * @param array|null $puery クエリパラメータ
     * @return void
     */
    public static function redirect($url, ?array $query = []) : void
    {
        if (!empty($query)) {
            $url = $url.(strpos($url, '?') !== false ? '&' : '?').http_build_query($query);
        }
        ob_clean();
        System::header("HTTP/1.1 302 Found");
        System::header("Location: {$url}");
        System::exit();
    }
    
    /**
     * データを JSON形式 で書き出します。
     * ※本メソッドは exit しません。
     *
     * @param array|object $data オブジェクト
     * @param string $charset 文字コード（デフォルト：UTF-8）
     */
    public static function json($data, string $charset = 'UTF-8') : void
    {
        ob_clean();
        System::header("HTTP/1.1 200 OK");
        System::header("Content-Type: application/json; charset={$charset}");
        echo json_encode($data);
        System::exit();
    }
    
    /**
     * データを JSONP形式 で書き出します。
     * ※本メソッドは exit しません。
     *
     * @param array|object $data オブジェクト
     * @param string $callback コールバック関数名
     * @param string $charset 文字コード（デフォルト：UTF-8）
     */
    public static function jsonp($data, string $callback, string $charset = 'UTF-8') : void
    {
        ob_clean();
        System::header("HTTP/1.1 200 OK");
        System::header("Content-Type: application/javascript; charset={$charset}");
        echo "{$callback}(".json_encode($data).")";
        System::exit();
    }

    /**
     * file_get_contents で指定URLのページデータを取得します。
     *
     * @param string $url URL
     * @return mixed 受信データ
     */
    public static function urlGetContents(string $url)
    {
        return file_get_contents($url, false, stream_context_create([
            'http' => ['ignore_errors' => true],
            'ssl'=> [
                'verify_peer' => false,
                'verify_peer_name' => false
            ],
        ]));
    }
}

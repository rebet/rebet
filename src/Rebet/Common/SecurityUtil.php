<?php
namespace Rebet\Common;

/**
 * セキュリティ関連 ユーティリティ クラス
 * 
 * セキュリティ関連の簡便なユーティリティメソッドを集めたクラスです。
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SecurityUtil {
    
    /**
     * インスタンス化禁止
     */
    private function __construct() {}

    /**
     * ランダムなコード文字列を生成します。
     * 
     * ex)
     * $init_pass = SecurityUtil::randomCode(12);
     * $sms_code  = SecurityUtil::randomCode(6, '1234567890');
     * 
     * @param int $length コード文字列の長さ
     * @param string $chars コード文字列に使用する文字（デフォルト：半角英数字）
     * @return string ランダムな文字列
     */
    public static function randomCode(int $length, string $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890") : string {
        $res = "";
        for ($i=0; $i < $length; $i++) {
            $res .= $chars{mt_rand(0, strlen($chars) - 1)};
        }
        return $res;
    }

    /**
     * 文字列をハッシュ化します。
     * 
     * ex)
     * $hash = SecurityUtil::hash('password');
     * $hash = SecurityUtil::hash('password', 'salt', 'pepper');
     * 
     * @param string $text ハッシュ対象文字列
     * @param string $salt ソルト値（デフォルト：空）
     * @param string $pepper ペッパー値（デフォルト：空）
     * @param string $algorithm ハッシュアルゴリズム（デフォルト：SHA256）
     * @param string $stretching ストレッチング回数（デフォルト：1000）
     * @return string ハッシュ文字列
     */
    public static function hash(string $text, string $salt = '', string $pepper = '', string $algorithm = 'SHA256', int $stretching = 1000) : string {
        for($i = 0 ; $i < $stretching ; $i++) {
            $text = hash($algorithm, $salt.md5($text).$pepper);
        }
        return $text;
    }
    
    /**
     * ランダムなをハッシュ値を生成します。
     * 
     * ex)
     * $hash = SecurityUtil::randomHash();
     * 
     * @param string $algorithm ハッシュアルゴリズム（デフォルト：SHA256）
     * @return string ハッシュ文字列
     */
    public static function randomHash(string $algorithm = 'SHA256') : string {
        return self::hash(date('Y-m-d H:i:s'), self::randomCode(8), self::randomCode(8), $algorithm, 10);
    }
    
    /**
     * 秘密鍵暗号で暗号化します。
     * 
     * ex)
     * $encrypted = NetUtil::encodeBase64Url(SecurityUtil::encript($text, 'secret_key'));
     * 
     * @param string $plain 平文
     * @param string $secretKey 秘密鍵
     * @param string $cipher 暗号器（デフォルト：AES-256-CBC）
     * @return string 暗号文
     * @see NetUtil::encodeBase64Url();
     */
    public static function encript($plain, $secretKey, $cipher='AES-256-CBC') {
        $iv_size   = openssl_cipher_iv_length($cipher);
        $iv        = random_bytes($iv_size);
        $encrypted = openssl_encrypt($plain, $cipher, $secretKey, OPENSSL_RAW_DATA, $iv);
        return $iv.$encrypted;
    }
    
    /**
     * 秘密鍵暗号で複合化します。
     * 
     * ex)
     * $decrypted = SecurityUtil::decript(NetUtil::decodeBase64Url($text), 'secret_key');
     * 
     * @param string encrypted 暗号文
     * @param string $secretKey 秘密鍵
     * @param string $cipher 暗号器（デフォルト：AES-256-CBC）
     * @return string 復号文
     */
    public static function decript($encrypted, $secretKey, $cipher='AES-256-CBC') {
        $iv_size   = openssl_cipher_iv_length($cipher);
        $iv        = substr($encrypted, 0, $iv_size);
        $encrypted = substr($encrypted, $iv_size);
        $decrypted = openssl_decrypt($encrypted, $cipher, $secretKey, OPENSSL_RAW_DATA, $iv);
        return rtrim($decrypted, "\0");
    }
}
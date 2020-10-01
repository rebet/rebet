<?php
namespace Rebet\Tools;

use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Configurable;

/**
 * Security Utility Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Securities
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'hash' => [
                'salt'       => Config::promise(function () { return getenv('DEFAULT_HASH_SALT') ?? ''; }),
                'pepper'     => Config::promise(function () { return getenv('DEFAULT_HASH_PEPPER') ?? ''; }),
                'algorithm'  => 'SHA256',
                'stretching' => 1000,
            ],
            'crypto' => [
                'secret_key' => Config::promise(function () { return getenv('SECRET_KEY') ?? null; }),
                'cipher'     => 'AES-256-CBC',
            ],
        ];
    }

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create a random code.
     *
     * ex)
     * $init_pass = Securities::randomCode(12);
     * $sms_code  = Securities::randomCode(6, '1234567890');
     *
     * @param int $length
     * @param string $chars (default: 1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890)
     * @return string
     */
    public static function randomCode(int $length, string $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890") : string
    {
        $res = "";
        for ($i=0; $i < $length; $i++) {
            $res .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $res;
    }

    /**
     * Hash the given text.
     * Note: If you want to hash the password, usually it is better to use password_hash() and password_verify() php functions.
     *
     * ex)
     * $hash = Securities::hash('password');
     * $hash = Securities::hash('password', 'salt', 'pepper');
     *
     * @param string $text
     * @param string $salt (default: depend on configure)
     * @param string $pepper (default: depend on configure)
     * @param string $algorithm (default: depend on configure)
     * @param string $stretching (default: depend on configure)
     * @return string
     */
    public static function hash(string $text, string $salt = null, string $pepper = null, string $algorithm = null, ?int $stretching = null) : string
    {
        $salt       = $salt ?? static::config('hash.salt') ;
        $pepper     = $pepper ?? static::config('hash.pepper') ;
        $algorithm  = $algorithm ?? static::config('hash.algorithm') ;
        $stretching = $stretching ?? static::config('hash.stretching') ;

        for ($i = 0 ; $i < $stretching ; $i++) {
            $text = hash($algorithm, $salt.md5($text).$pepper);
        }
        return $text;
    }

    /**
     * Generate a random hash value.
     *
     * ex)
     * $hash = Securities::randomHash();
     *
     * @param string $algorithm (default: depend on configure)
     * @return string
     */
    public static function randomHash(string $algorithm = null) : string
    {
        $algorithm = $algorithm ?? static::config('hash.algorithm') ;
        return self::hash(date('Y-m-d H:i:s'), self::randomCode(8), self::randomCode(8), $algorithm, 10);
    }

    /**
     * Encrypt with private key encryption by openssl_encrypt().
     *
     * ex)
     * $encrypted = Nets::encodeBase64Url(Securities::encrypt($text, 'secret_key'));
     *
     * @param string $plain
     * @param string $secret_key (default: depend on configure)
     * @param string $cipher (default: depend on configure)
     * @return string
     * @see Nets::encodeBase64Url();
     */
    public static function encrypt($plain, $secret_key = null, $cipher = null)
    {
        $secret_key = $secret_key ?? static::config('crypto.secret_key') ;
        $cipher     = $cipher ?? static::config('crypto.cipher') ;
        $iv_size    = openssl_cipher_iv_length($cipher);
        $iv         = random_bytes($iv_size);
        $encrypted  = openssl_encrypt($plain, $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
        return $iv.$encrypted;
    }

    /**
     * Decrypt with private key decryption by openssl_decrypt().
     *
     * ex)
     * $decrypted = Securities::decrypt(Nets::decodeBase64Url($text), 'secret_key');
     *
     * @param string encrypted
     * @param string $secret_key (default: depend on configure)
     * @param string $cipher (default: depend on configure)
     * @return string
     */
    public static function decrypt($encrypted, $secret_key = null, $cipher = null)
    {
        $secret_key = $secret_key ?? static::config('crypto.secret_key') ;
        $cipher     = $cipher ?? static::config('crypto.cipher') ;
        $iv_size    = openssl_cipher_iv_length($cipher);
        $iv         = substr($encrypted, 0, $iv_size);
        $encrypted  = substr($encrypted, $iv_size);
        $decrypted  = openssl_decrypt($encrypted, $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
        return rtrim($decrypted, "\0");
    }
}

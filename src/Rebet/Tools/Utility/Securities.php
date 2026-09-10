<?php
declare(strict_types=1);

namespace Rebet\Tools\Utility;

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

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/tools.lp.php
     */
    public static function defaultConfig()
    {
        return [
            'hash' => [
                'salt'       => Env::promise('DEFAULT_HASH_SALT', null, false),
                'pepper'     => Env::promise('DEFAULT_HASH_PEPPER', null, false),
                'algorithm'  => 'SHA256',
                'stretching' => 1,
            ],
            'hmac' => [
                'secret_key' => Env::promise('DEFAULT_HMAC_SECRET_KEY', null, false),
                'algorithm'  => 'SHA256',
            ],
            'crypto' => [
                'secret_key' => Env::promise('DEFAULT_CRYPTO_SECRET_KEY', null, false),
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
        for ($i = 0; $i < $length; $i++) {
            $res .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $res;
    }

    /**
     * Hash the given text.
     * Note: If you want to hash the password, you MUST use Rebet\Auth\Password (or password_hash() and password_verify() php functions).
     *
     * ex)
     * $hash = Securities::hash('text');
     * $hash = Securities::hash('text', 'salt', 'pepper');
     *
     * @param string $text
     * @param string|null $salt (default: depend on configure)
     * @param string|null $pepper (default: depend on configure)
     * @param string|null $algorithm (default: depend on configure)
     * @param int|null $stretching (default: depend on configure)
     * @return string
     */
    public static function hash(string $text, string|null $salt = null, string|null $pepper = null, string|null $algorithm = null, int|null $stretching = null) : string
    {
        $salt       = $salt ?? static::config('hash.salt') ;
        $pepper     = $pepper ?? static::config('hash.pepper') ;
        $algorithm  = $algorithm ?? static::config('hash.algorithm') ;
        $stretching = $stretching ?? static::config('hash.stretching') ;

        for ($i = 0 ; $i < $stretching ; $i++) {
            $text = hash($algorithm, $salt."\0".$text."\0".$pepper);
        }
        return $text;
    }

    /**
     * Generate an HMAC for the given text.
     * Note: If you want to hash the password, you MUST use Rebet\Auth\Password (or password_hash() and password_verify() php functions).
     *
     * ex)
     * $hash = Securities::hmac('text');
     * $hash = Securities::hmac('text', 'secret_key', 'algorithm');
     *
     * @param string $text
     * @param string|null $secret_key (default: depend on configure)
     * @param string|null $algorithm (default: depend on configure)
     * @return string
     */
    public static function hmac(string $text, string|null $secret_key = null, string|null $algorithm = null) : string
    {
        $secret_key = $secret_key ?? static::config('hmac.secret_key') ;
        $algorithm  = $algorithm ?? static::config('hmac.algorithm') ;

        return hash_hmac($algorithm, $text, $secret_key);
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
    public static function randomHash(string|null $algorithm = null) : string
    {
        $algorithm = $algorithm ?? static::config('hash.algorithm') ;
        return self::hash(date('Y-m-d H:i:s'), self::randomCode(8), self::randomCode(8), $algorithm, 10);
    }

    /**
     * Encrypt with private key encryption by openssl_encrypt(), then apply Encrypt-then-MAC using
     * Securities::hmac() (with an independent HMAC secret_key, not shared with the cipher's
     * secret_key) so that any tampering of the returned payload can be detected before it is ever
     * passed to openssl_decrypt(), avoiding padding-oracle style attacks against plain AES-CBC.
     *
     * ex)
     * $encrypted = Nets::encodeBase64Url(Securities::encrypt($text, 'secret_key'));
     *
     * @param string $plain
     * @param string|null $secret_key (default: depend on configure)
     * @param string|null $cipher (default: depend on configure)
     * @param string|null $hmac_secret_key (default: depend on configure)
     * @param string|null $hmac_algorithm (default: depend on configure)
     * @return string
     * @see Nets::encodeBase64Url();
     */
    public static function encrypt(string $plain, string|null $secret_key = null, string|null $cipher = null, string|null $hmac_secret_key = null, string|null $hmac_algorithm = null)
    {
        $secret_key = $secret_key ?? static::config('crypto.secret_key') ;
        $cipher     = $cipher ?? static::config('crypto.cipher') ;
        $iv_size    = openssl_cipher_iv_length($cipher);
        $iv         = random_bytes($iv_size);
        $encrypted  = openssl_encrypt($plain, $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
        $payload    = $iv.$encrypted;
        return static::hmac($payload, $hmac_secret_key, $hmac_algorithm).$payload;
    }

    /**
     * Verify the Encrypt-then-MAC tag then decrypt with private key decryption by
     * openssl_decrypt(). Returns null when the MAC does not match, ie. the given value was not
     * generated by Securities::encrypt() (using the same keys) or has been tampered with.
     *
     * ex)
     * $decrypted = Securities::decrypt(Nets::decodeBase64Url($text), 'secret_key');
     *
     * @param string $encrypted
     * @param string|null $secret_key (default: depend on configure)
     * @param string|null $cipher (default: depend on configure)
     * @param string|null $hmac_secret_key (default: depend on configure)
     * @param string|null $hmac_algorithm (default: depend on configure)
     * @return string|null
     */
    public static function decrypt(string $encrypted, string|null $secret_key = null, string|null $cipher = null, string|null $hmac_secret_key = null, string|null $hmac_algorithm = null)
    {
        $secret_key = $secret_key ?? static::config('crypto.secret_key') ;
        $cipher     = $cipher ?? static::config('crypto.cipher') ;

        $mac_size = strlen(static::hmac('', $hmac_secret_key, $hmac_algorithm));
        $mac      = substr((string) $encrypted, 0, $mac_size);
        $payload  = substr((string) $encrypted, $mac_size);
        if (!hash_equals(static::hmac($payload, $hmac_secret_key, $hmac_algorithm), $mac)) {
            return null;
        }

        $iv_size   = openssl_cipher_iv_length($cipher);
        $iv        = substr($payload, 0, $iv_size);
        $encrypted = substr($payload, $iv_size);
        $decrypted = openssl_decrypt($encrypted, $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
        return rtrim((string) $decrypted, "\0");
    }
}

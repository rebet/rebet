<?php
namespace Rebet\Auth;

use Rebet\Tools\Config\Configurable;

/**
 * Password Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Password
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/auth.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'algorithm' => PASSWORD_DEFAULT,
            'options'   => [],
        ];
    }

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Generate password hash from given plain password using password_hash() php function.
     *
     * @param string|null $password
     * @param int|string|null $algorithm (default: depend on configure)
     * @param array<string, mixed>|null $options (default: depend on configure)
     * @return string|null
     */
    public static function hash(string|null $password, $algorithm = null, array|null $options = null) : string|null
    {
        if ($password === null) {
            return null;
        }
        $algorithm = $algorithm ?? static::config('algorithm');
        $options   = $options ?? static::config('options', false, []);
        return password_hash($password, $algorithm, $options);
    }

    /**
     * Verify the given password and password hash.
     *
     * @param string|null $password
     * @param string|null $hash
     * @return boolean
     */
    public static function verify(string|null $password, string|null $hash) : bool
    {
        return ($password === null || $hash === null) ? false : password_verify($password, $hash) ;
    }

    /**
     * It checks the password needs rehash.
     *
     * @param string|null $hash
     * @param int|string|null $algorithm (default: depend on configure)
     * @param array<string, mixed>|null $options (default: depend on configure)
     * @return bool
     */
    public static function needsRehash(string|null $hash, $algorithm = null, array|null $options = null) : bool
    {
        if ($hash === null) {
            return false;
        }
        $algorithm = $algorithm ?? static::config('algorithm');
        $options   = $options ?? static::config('options', false, []);
        return password_needs_rehash($hash, $algorithm, $options);
    }
}

<?php
namespace Rebet\Auth;

use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Testable\System;

/**
 * Authentication Related Utility Class
 *
 * A class that gathers simple utility methods related to authentication.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BasicAuth
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Provides simple BASIC authentication.
     *
     * @param array $auth_list Authentication list
     * @param ?\Closure $to_hash Password hashing logic for the authentication list (default: null)
     * @param string $realm Realm text
     * @param string $failed_text Message on authentication failure
     * @param string $charset Character encoding (default: UTF-8)
     * @return string
     */
    public static function authenticate(array $auth_list, ?\Closure $to_hash = null, string $realm = "Enter your ID and PASSWORD.", string $failed_text = "Authenticate Failed.", string $charset = 'UTF-8') : string
    {
        if (empty($to_hash)) {
            $to_hash = function ($password) {
                return $password;
            };
        }

        $user      = Reflector::get($_SERVER, 'PHP_AUTH_USER');
        $pass      = $to_hash(Reflector::get($_SERVER, 'PHP_AUTH_PW')) ;
        $auth_pass = Reflector::get($auth_list, $user);
        if (!empty($user) && !empty($pass) && $auth_pass === $pass) {
            return $user;
        }

        System::header('HTTP/1.0 401 Unauthorized');
        System::header('WWW-Authenticate: Basic realm="'.$realm.'"');
        System::header('Content-type: text/html; charset='.$charset);

        throw new AuthenticateException($failed_text);
    }
}

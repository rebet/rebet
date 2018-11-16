<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Common\Securities;

/**
 * Abstract Auth Provider Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class AuthProvider
{
    /**
     * Find user by id then check the user state by checker.
     *
     * @param mixed $id
     * @param callable|null $checker function (user) : bool {...}
     * @return AuthUser|null
     */
    abstract public function findById($id, ?callable $checker = null) : ?AuthUser ;

    /**
     * Find user by signin_id then check the user state by checker.
     * The signin_id may be a login ID, a email address or member number, but it must be unique.
     *
     * @param mixed $signin_id
     * @param callable|null $checker
     * @return AuthUser|null
     */
    abstract public function findBySigninId($signin_id, ?callable $checker = null) : ?AuthUser ;

    /**
     * Find user by remember token then check the user state by checker.
     *
     * @param string $token
     * @param callable|null $checker
     * @return AuthUser|null
     */
    abstract public function findByRememberToken(string $token, ?callable $checker = null) : ?AuthUser ;

    /**
     * Issuing remember token and return the token.
     *
     * @param mixed $id
     * @param integer $remember_days
     * @return string
     */
    abstract public function issuingRememberToken($id, int $remember_days) : string ;

    /**
     * Generate token.
     *
     * @param integer $length (default: 40)
     * @return string
     */
    protected function generateToken(int $length = 40) : string
    {
        return Securities::randomCode($length);
    }
}

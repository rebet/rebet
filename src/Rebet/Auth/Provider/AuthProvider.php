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
     * Authenticator name of this provider.
     *
     * @var string
     */
    protected $authenticator = null;

    /**
     * Find user by id.
     *
     * @param mixed $id
     * @return AuthUser|null
     */
    abstract public function findById($id) : ?AuthUser ;

    /**
     * Find user by signin_id.
     * The signin_id may be a login ID, a email address or member number, but it must be unique.
     *
     * @param array|Arrayable $credentials ['signin_id' => id, 'password' => password]
     * @return AuthUser|null
     */
    abstract public function findByCredentials($credentials) : ?AuthUser ;

    /**
     * It checks the provider will support remember token.
     * If this provider support remember token must be override the method in sub class.
     *
     * @return boolean
     */
    public function supportRememberToken() : bool
    {
        return false;
    }

    /**
     * Find user by remember token.
     * If this provider support remember token must be override the method in sub class.
     *
     * @param string $token
     * @return AuthUser|null
     */
    public function findByRememberToken(string $token) : ?AuthUser
    {
        return null;
    }

    /**
     * Issuing remember token and return the token.
     * If this provider support remember token must be override the method in sub class.
     *
     * @param mixed $id
     * @param integer $remember_days
     * @return string|null token
     */
    public function issuingRememberToken($id, int $remember_days) : ?string
    {
        return null;
    }

    /**
     * Remove the given remember token.
     * If this provider support remember token must be override the method in sub class.
     *
     * @param string|null $token
     * @return void
     */
    public function removeRememberToken(?string $token) : void
    {
        // Do nothing.
    }

    /**
     * Get and Set authenticator name of this provider.
     *
     * @param string|null $name
     * @return mixed
     */
    public function authenticator(?string $name = null)
    {
        return $name === null ? $this->authenticator : $this->authenticator = $name ;
    }
    
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

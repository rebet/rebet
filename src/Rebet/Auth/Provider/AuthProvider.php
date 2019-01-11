<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Password;
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
     * Find user by token.
     *
     * @param string $token_name
     * @param string $token
     * @param mixed $precondition (default: null)
     * @return AuthUser|null
     */
    abstract public function findByToken(string $token_name, ?string $token, $precondition = null) : ?AuthUser ;

    /**
     * Find user by signin_id and password.
     * The signin_id may be named 'login_id', 'email', etc.
     *
     * @param mixed $signin_id
     * @param string|null $password
     * @param mixed $precondition (default: null)
     * @return AuthUser|null
     */
    public function findByCredentials($signin_id, ?string $password, $precondition = null) : ?AuthUser
    {
        $user = $this->findBySigninId($signin_id, $precondition);
        if ($user === null) {
            return null;
        }

        if (!Password::verify($password, $user->password)) {
            return null;
        }

        if (Password::needsRehash($user->password)) {
            $this->rehashPassword($user->id, Password::hash($password));
        }

        return $user;
    }

    /**
     * Find user by signin_id.
     * The signin_id may be named 'login_id', 'email', etc.
     *
     * @param mixed $value
     * @param mixed $precondition (default: null)
     * @return AuthUser|null
     */
    abstract protected function findBySigninId($signin_id, $precondition = null) : ?AuthUser ;

    /**
     * Save rehash password.
     * If sub class not support password rehash then override by empty implements.
     *
     * @param mixed $id
     * @param string $new_hash
     * @return void
     */
    abstract public function rehashPassword($id, string $new_hash) : void ;

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
     * @param string|null $token
     * @param mixed $precondition (default: null)
     * @return AuthUser|null
     */
    public function findByRememberToken(?string $token, $precondition = null) : ?AuthUser
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
     * @return self|string|null
     */
    public function authenticator(?string $name = null)
    {
        if($name === null) {
            return $this->authenticator;
        }
        $this->authenticator = $name;
        return $this;
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

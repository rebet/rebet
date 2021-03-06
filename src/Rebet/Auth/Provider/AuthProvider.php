<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Password;
use Rebet\Tools\Utility\Securities;

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
     * The name of this provider.
     *
     * @var string
     */
    protected $name = null;

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
     * @param string|null $token
     * @return AuthUser|null
     */
    abstract public function findByToken(?string $token) : ?AuthUser ;

    /**
     * Find user by signin_id and password.
     * The signin_id may be named 'login_id', 'email', etc.
     *
     * @param mixed $signin_id
     * @param string|null $password
     * @return AuthUser|null
     */
    public function findByCredentials($signin_id, ?string $password) : ?AuthUser
    {
        $user = $this->findBySigninId($signin_id);
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
     * @return AuthUser|null
     */
    abstract protected function findBySigninId($signin_id) : ?AuthUser ;

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
     * @return AuthUser|null
     */
    public function findByRememberToken(?string $token) : ?AuthUser
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
     * Get and Set name of this provider.
     *
     * @param string|null $name
     * @return self|string|null
     */
    public function name(?string $name = null)
    {
        if ($name === null) {
            return $this->name;
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Generate token.
     *
     * @param integer $length (default: 60)
     * @return string
     */
    protected function generateToken(int $length = 60) : string
    {
        return Securities::randomCode($length);
    }

    /**
     * Hash given token.
     *
     * @param string $token
     * @return string|null
     */
    protected function hashToken(?string $token) : ?string
    {
        return $token ? Securities::hash($token) : null ;
    }
}

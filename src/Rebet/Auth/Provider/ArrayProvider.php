<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Tools\Tinker\Tinker;

/**
 * Array Auth Provider Class
 *
 * This is readonly provider depend on configure.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArrayProvider extends AuthProvider
{
    /**
     * Users map data.
     *
     * @var Tinker
     */
    protected $users;

    /**
     * Sign in id attribute name
     *
     * @var string
     */
    protected $signin_id_name = null;

    /**
     * API token attribute name
     *
     * @var string
     */
    protected $token_name = null;

    /**
     * Preconditions for signin id authenticate.
     *
     * @var callable
     */
    protected $precondition = null;

    /**
     * Create a readonly array provider.
     * Users information must have the following data,
     *
     *  - 'user_id'   => id of user
     *  - 'role'      => role ('user', 'admin', etc)
     *  - 'email'     => email address   (for credentials)
     *  - 'password'  => hashed password (for credentials)
     *
     * And if you want to add other information, you can add attribute to users record.
     *
     * @param array $users
     * @param string|null $signin_id_name (default: 'email')
     * @param string $token_name (default: 'api_token')
     * @param callable|null $precondition function($user):bool {...} (default: `function ($user) { return true; }`)
     */
    public function __construct(array $users, ?string $signin_id_name = 'email', string $token_name = 'api_token', ?callable $precondition = null)
    {
        $this->users          = Tinker::with($users, true);
        $this->signin_id_name = $signin_id_name;
        $this->token_name     = $token_name;
        $this->precondition   = $precondition ?? function ($user) { return true; };
    }

    /**
     * {@inheritDoc}
     */
    public function findById($id) : ?AuthUser
    {
        return $this->users
            ->first(function ($user) use ($id) { return $user['user_id'] == $id; })
            ->return(function ($user) { return new AuthUser($user, [], $this); });
    }

    /**
     * {@inheritDoc}
     */
    public function findByToken(?string $token) : ?AuthUser
    {
        return $this->users
            ->where(function ($user) use ($token) { return $user[$this->token_name] == $this->hashToken($token); })
            ->where($this->precondition)
            ->first()
            ->return(function ($user) { return new AuthUser($user, [], $this); });
    }

    /**
     * {@inheritDoc}
     */
    protected function findBySigninId($signin_id) : ?AuthUser
    {
        return $this->users
            ->where(function ($user) use ($signin_id) { return $user[$this->signin_id_name] == $signin_id; })
            ->where($this->precondition)
            ->first()
            ->return(function ($user) { return new AuthUser($user, [], $this); });
    }

    /**
     * {@inheritDoc}
     */
    public function rehashPassword($id, string $new_hash) : void
    {
        // Nothing to do (Password rehash not supported)
    }
}

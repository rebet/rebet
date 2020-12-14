<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Http\Response;

/**
 * Stateful Guard Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class StatefulGuard extends Guard
{
    /**
     * Number of days to remember me.
     *
     * @var int
     */
    protected $remember_days;

    /**
     * Create a stateful guard instance.
     *
     * @param string $provider name of configured in `Auth.providers.{name}`.
     * @param int $remember_days (default: 0)
     */
    public function __construct(string $provider, int $remember_days = 0)
    {
        parent::__construct($provider);
        $this->remember_days = $remember_days;
    }

    /**
     * Attempt find user by given credentials.
     *
     * @param mixed $signin_id
     * @param string|null $password
     * @return AuthUser
     */
    public function attempt($signin_id, ?string $password) : AuthUser
    {
        $user = $this->provider->findByCredentials($signin_id, $password);
        return $user ? $user : AuthUser::guest($signin_id);
    }

    /**
     * Signin a given authenticated user.
     *
     * @param AuthUser $user
     * @param string $goto (default: '/')
     * @param boolean $remember (default: false)
     * @return Response
     */
    abstract public function signin(AuthUser $user, string $goto = '/', bool $remember = false) : Response;

    /**
     * It will sign out the authenticated user.
     *
     * @param string $goto (default: '/')
     * @return Response
     */
    abstract public function signout(string $goto = '/') : Response;

    /**
     * Get the 'remember me' days period.
     *
     * @return int of days
     */
    public function getRememberDays() : int
    {
        return $this->remember_days;
    }
}

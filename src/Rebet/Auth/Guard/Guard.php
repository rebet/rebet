<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Http\Response;

/**
 * Guard Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Guard
{
    /**
     * Get the name of this guard.
     *
     * @var string|null
     */
    protected $name = null;

    /**
     * Authenticated user
     *
     * @var AuthUser|null
     */
    protected $user = null;

    /**
     * Auth provider of this guard
     *
     * @var AuthProvider
     */
    protected $provider;

    /**
     * Create a guard instance.
     *
     * @param string $provider name of configured in `Auth.providers.{name}`.
     */
    public function __construct(string $provider)
    {
        $this->provider = Auth::provider($provider);
    }

    /**
     * Get/Set the name of this guard.
     *
     * @param string|null $name (default: null for get name)
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
     * Get authentication provider of this guard.
     *
     * @return AuthProvider
     */
    public function provider() : AuthProvider
    {
        return $this->provider;
    }

    /**
     * Get the authenticated user.
     *
     * @return AuthUser
     */
    public function user() : AuthUser
    {
        return $this->user ?? $this->user = AuthUser::guest()->guest($this) ;
    }

    /**
     * Authenticate user.
     *
     * @return Response|null response of fallback when authenticate failed
     */
    abstract public function authenticate() : ?Response;
}

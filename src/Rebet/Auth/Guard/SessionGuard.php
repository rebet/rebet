<?php
namespace Rebet\Auth\Guard;

/**
 * Session Guard Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SessionGuard implements Guard
{
    /**
     * {@inheritDoc}
     */
    public function signin(Request $request, AuthProvider $provider, callable $checker, bool $remember = false) : AuthUser
    {
    }
    
    /**
     * {@inheritDoc}
     */
    public function signout(Request $request, AuthProvider $provider, AuthUser $user, string $redirect_to) : Response
    {
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request, AuthProvider $provider, callable $checker) : AuthUser
    {
    }
}

<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Http\Request;
use Rebet\Http\Response;

/**
 * Guard Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Guard
{
    /**
     * Signin an incoming request.
     * If signin failed then return AuthUser::guest().
     *
     * @param Request $request
     * @param AuthProvider $provider
     * @param callable $checker function($user):bool
     * @param boolean $remember
     * @return AuthUser
     */
    public function signin(Request $request, AuthProvider $provider, callable $checker, bool $remember = false) : AuthUser;
    
    /**
     * It will sign out the authenticated user.
     *
     * @param Request $request
     * @param AuthProvider $provider
     * @param AuthUser $user
     * @param string $redirect_to
     * @return Response
     */
    public function signout(Request $request, AuthProvider $provider, AuthUser $user, string $redirect_to) : Response;

    /**
     * Recall authenticate user from an incoming request.
     *
     * @param Request $request
     * @param AuthProvider $provider
     * @param callable $checker function($user):bool
     * @return AuthUser
     */
    public function authenticate(Request $request, AuthProvider $provider, callable $checker) : AuthUser;
}

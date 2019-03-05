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
     * Signin a given authenticated user.
     *
     * @param Request $request
     * @param AuthUser $user
     * @param boolean $remember
     * @return void
     */
    public function signin(Request $request, AuthUser $user, bool $remember = false) : void;

    /**
     * It will sign out the authenticated user.
     *
     * @param Request $request
     * @param AuthUser $user
     * @param string $redirect_to (default: '/')
     * @return Response
     */
    public function signout(Request $request, AuthUser $user, string $redirect_to = '/') : Response;

    /**
     * Recall authenticate user from an incoming request.
     *
     * @param Request $request
     * @param AuthProvider $provider
     * @return AuthUser
     */
    public function authenticate(Request $request, AuthProvider $provider) : AuthUser;

    /**
     * Get and Set authenticator name of this guard.
     *
     * @param string|null $name
     * @return mixed
     */
    public function authenticator(?string $name = null) ;
}

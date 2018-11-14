<?php
namespace Rebet\Auth;

use Rebet\Auth\Guard\Guard;
use Rebet\Config\Configurable;

/**
 * Auth Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Auth
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'authenticator' => [
                'web' => [
                    'guard'    => null,
                    'provider' => null,
                    'fallback' => null, // url or function (Request) : Response { ... }
                ],
                'api' => [
                    'guard'    => null,
                    'provider' => null,
                    'fallback' => null, // url or function (Request) : Response { ... }
                ],
            ],
        ];
    }

    /**
     * Authenticated user
     *
     * @var AuthUser
     */
    protected static $user = null;

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get the authenticated user.
     *
     * @param string $name
     * @return Guard
     */
    public static function user() : AuthUser
    {
        return static::$user ?? AuthUser::guest() ;
    }

    /**
     * Authenticate an incoming request.
     *
     * @param Request $request
     * @return Response|null response when authenticate failed
     */
    public static function authenticate(Request $request, bool $remember = false) : ?Response
    {
        $route    = $request->route;
        $auth     = $route->auth() ?? $request->channel ;
        $guard    = static::configInstantiate("authenticator.{$auth}.guard");
        $provider = static::configInstantiate("authenticator.{$auth}.provider");

        $user = $guard->authenticate($request, $provider, $remember);

        $roles = $route->roles();
        if (in_array($user->role(), $roles) || in_array('ALL', $roles)) {
            static::$user = $user;
            return null;
        }
        
        $fallback = static::config("authenticator.{$auth}.fallback");
        return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
    }

    /**
     * Authenticate recall an incoming request.
     *
     * @param Request $request
     * @return Response|null response when authenticate failed
     */
    public static function recall(Request $request) : ? Response
    {
        $route    = $request->route;
        $auth     = $route->auth() ?? $request->channel;
        $guard    = static::configInstantiate("authenticator.{$auth}.guard");
        $provider = static::configInstantiate("authenticator.{$auth}.provider");

        $user = $guard->recall($request, $provider);

        $roles = $route->roles();
        if (in_array($user->role(), $roles) || in_array('ALL', $roles)) {
            static::$user = $user;
            return null;
        }
        
        $fallback = static::config("authenticator.{$auth}.fallback");
        return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
    }
}

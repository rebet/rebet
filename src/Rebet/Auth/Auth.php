<?php
namespace Rebet\Auth;

use Rebet\Auth\Guard\Guard;
use Rebet\Auth\Guard\NoGuard;
use Rebet\Config\Configurable;
use Rebet\Common\Reflector;



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
    public static function authenticate(Request $request) : ?Response
    {
        $channel  = $request->channel;
        $route    = $request->route;
        $prefix   = $route->prefix;
        $guard    = Reflector::instantiate(static::authenticator('guard', $channel, $prefix));
        $provider = Reflector::instantiate(static::authenticator('provider', $channel, $prefix));

        $user = $guard->recall($request, $provider);

        $roles = $route->roles();
        if(!in_array($user->role(), $roles) && !in_array('ALL', $roles)) {
            $fallback = static::authenticator('fallback', $channel, $prefix);
            return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
        }
        static::$user = $user;
        return null;
    }

    /**
     * Get authenticator configuration
     *
     * @param string $type
     * @param string $channel
     * @param string $prefix
     * @return mixed
     */
    protected static function authenticator(string $type, string $channel, string $prefix)
    {
        return static::config("{$channel}{$prefix}.{$type}", false) ?? static::config("{$channel}.{$type}") ;
    }
}

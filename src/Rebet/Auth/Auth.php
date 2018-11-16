<?php
namespace Rebet\Auth;

use Rebet\Auth\Event\Authenticated;
use Rebet\Auth\Event\Signined;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Auth\Event\Signouted;
use Rebet\Auth\Guard\Guard;
use Rebet\Config\Configurable;
use Rebet\Event\Event;
use Rebet\Http\Responder;
use Rebet\Validation\ValidData;
use Rebet\Auth\Guard\SessionGuard;

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
                    'guard'       => SessionGuard::class,
                    'provider'    => null,
                    'checker'     => null, // function (AuthUser $user, ?string $password) { return true; },
                    'fallback'    => null, // url or function(Request):Response
                    'credentials' => ['signin_id' => 'email', 'password'  => 'password'],
                ],
                'api' => [
                    'guard'       => null,
                    'provider'    => null,
                    'checker'     => null, // function (AuthUser $user, ?string $password) { return true; },
                    'fallback'    => null, // url or function(Request):Response
                    'credentials' => ['signin_id' => 'email', 'password'  => 'password'],
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
     * Signin an incoming request.
     *
     * @param Request $request
     * @param array $credentials
     * @param bool $remember (default: false)
     * @param string|null $authenticator (default: depend on routing configure)
     * @return AuthUser|null null when authenticate failed
     */
    public static function signin(Request $request, array $credentials, bool $remember = false, ?string $authenticator = null) : ?AuthUser
    {
        $route    = $request->route;
        $auth     = $authenticator ?? $route->auth() ?? $request->channel ;
        $guard    = static::configInstantiate("authenticator.{$auth}.guard");
        $provider = static::configInstantiate("authenticator.{$auth}.provider");
        $checker  = \Closure::fromCallable(static::config("authenticator.{$auth}.checker"));
        $guard->authenticator($auth);

        $user = $guard->signin($request, $provider, $checker, $remember);
        if ($user->isGuest()) {
            Event::dispatch(new SigninFailed($request));
            return null;
        }

        $user->authenticator = $auth;
        static::$user        = $user;
        Event::dispatch(new Signined($request, $user, $remember));
        return $user;
    }

    /**
     * It will sign out the authenticated user.
     *
     * @param Request $request
     * @param string $redirect_to
     * @return Response
     */
    public static function signout(Request $request, string $redirect_to) : Response
    {
        if (static::$user === null || static::$user->isGuest()) {
            Responder::redirect($redirect_to);
        }

        $user     = static::user();
        $guard    = $user->guard();
        $provider = $user->provider();
        $response = $guard->signout($request, $provider, $user, $redirect_to);

        static::$user = AuthUser::guest();
        Event::dispatch(new Signouted($request, $user));
        return $response;
    }

    /**
     * Recall authenticate user from an incoming request then it will check to match the user's role in allowed roles of route.
     *
     * @param Request $request
     * @return Response|null response when authenticate failed
     */
    public static function authenticate(Request $request) : ? Response
    {
        $route    = $request->route;
        $auth     = $route->auth() ?? $request->channel;
        $guard    = static::configInstantiate("authenticator.{$auth}.guard");
        $provider = static::configInstantiate("authenticator.{$auth}.provider");
        $checker  = \Closure::fromCallable(static::config("authenticator.{$auth}.checker"));

        $user = $guard->authenticate($request, $provider, $checker);

        $roles = $route->roles();
        if (in_array($user->role(), $roles) || in_array('ALL', $roles)) {
            $user->authenticator = $auth;
            static::$user        = $user;
            Event::dispatch(new Authenticated($request, $user));
            return null;
        }
        
        $fallback = static::config("authenticator.{$auth}.fallback");
        return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
    }
}

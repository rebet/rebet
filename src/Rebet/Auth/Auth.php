<?php
namespace Rebet\Auth;

use Rebet\Auth\Event\Authenticated;
use Rebet\Auth\Event\Signined;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Auth\Event\Signouted;
use Rebet\Auth\Guard\Guard;
use Rebet\Config\Configurable;
use Rebet\Event\Event;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;

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
                    'fallback' => null, // url or function(Request):Response
                ],
                'api' => [
                    'guard'    => null,
                    'provider' => null,
                    'fallback' => null, // url or function(Request):Response
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
     * Attempt find user by given credentials.
     *
     * @param Request $request
     * @param array $credentials
     * @param string|null $authenticator (default: auth of the route, if not set then use channel name)
     * @return AuthUser|null
     */
    public static function attempt(Request $request, array $credentials, ?string $authenticator = null) : ?AuthUser
    {
        $route    = $request->route;
        $auth     = $authenticator ?? $route->auth() ?? $request->channel ;
        $provider = static::configInstantiate("authenticator.{$auth}.provider");
        $user     = $provider->findByCredentials($request, $credentials);
        if ($user) {
            $provider->authenticator($auth);
            $user->provider($provider);

            $guard = static::configInstantiate("authenticator.{$auth}.guard");
            $guard->authenticator($auth);
            $user->guard($guard);
        }
        return $user;
    }

    /**
     * Sign in as given user.
     * If the given user is null or guest, then this method return false and dispatch SigninFailed event.
     *
     * @param Request $request
     * @param AuthUser|null $user
     * @param bool $remember (default: false)
     * @return bool
     */
    public static function signin(Request $request, ?AuthUser $user, bool $remember = false) : bool
    {
        if ($user === null || $user->isGuest()) {
            Event::dispatch(new SigninFailed($request));
            return false;
        }

        $user->guard()->signin($request, $user, $remember);
        static::$user = $user;
        Event::dispatch(new Signined($request, $user, $remember));
        return true;
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
        $user = static::user();
        if ($user === null || $user->isGuest()) {
            return Responder::redirect($redirect_to);
        }

        $response     = $user->guard()->signout($request, $user, $redirect_to);
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
    public static function authenticate(Request $request) : ?Response
    {
        $route    = $request->route;
        $auth     = $route->auth() ?? $request->channel;
        $guard    = static::configInstantiate("authenticator.{$auth}.guard");
        $provider = static::configInstantiate("authenticator.{$auth}.provider");
        $guard->authenticator($auth);
        $provider->authenticator($auth);

        $user = $guard->authenticate($request, $provider);
        $user->provider($provider);
        $user->guard($guard);
        static::$user = $user;

        $roles = $route->roles();
        if (in_array($user->role(), $roles) || in_array('ALL', $roles)) {
            Event::dispatch(new Authenticated($request, $user));
            return null;
        }
        
        $fallback = static::config("authenticator.{$auth}.fallback");
        return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
    }
}

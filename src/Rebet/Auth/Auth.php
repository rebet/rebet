<?php
namespace Rebet\Auth;

use Rebet\Auth\Event\Authenticate;
use Rebet\Auth\Event\Signin;
use Rebet\Auth\Event\Signout;
use Rebet\Auth\Guard\Guard;
use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\Http\Responder;

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
                    'checker'  => function ($user) { return true; },
                    'fallback' => null, // url or function(Request):Response
                ],
                'api' => [
                    'guard'    => null,
                    'provider' => null,
                    'checker'  => function ($user) { return true; },
                    'fallback' => null, // url or function(Request):Response
                ],
            ],
            'listeners' => [],
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
     * @param string|null $authenticator (default: depend on routing configure)
     * @return AuthUser|null response when authenticate failed
     */
    public static function signin(Request $request, ?string $authenticator = null, bool $remember = false) : ?AuthUser
    {
        $route    = $request->route;
        $auth     = $authenticator ?? $route->auth() ?? $request->channel ;
        $guard    = static::configInstantiate("authenticator.{$auth}.guard");
        $provider = static::configInstantiate("authenticator.{$auth}.provider");
        $checker  = static::configInstantiate("authenticator.{$auth}.checker");

        $user = $guard->signin($request, $provider, $checker, $remember);
        if ($user->isGuest()) {
            return null;
        }

        $user->authenticator = $auth;
        static::$user        = $user;
        static::dispatch(new Signin($request, $user, $remember));
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
        static::dispatch(new Signout($request, $user));
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
        $checker  = static::configInstantiate("authenticator.{$auth}.checker");

        $user = $guard->authenticate($request, $provider, $checker);

        $roles = $route->roles();
        if (in_array($user->role(), $roles) || in_array('ALL', $roles)) {
            $user->authenticator = $auth;
            static::$user        = $user;
            static::dispatch(new Authenticate($request, $user));
            return null;
        }
        
        $fallback = static::config("authenticator.{$auth}.fallback");
        return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
    }

    /**
     * Add auth event listener.
     * An auth event listener must have handle(EventClass $event) method with type hinting of event class.
     *
     * @param mixed $listener
     * @return void
     */
    public static function addListener($listener) : void
    {
        static::setConfig(['listeners' => [$listener]]);
    }

    /**
     * Dispatch the given event to auth event listeners.
     *
     * @param mixed $event
     * @return void
     */
    public static function dispatch($event) : void
    {
        foreach (static::config('listeners', false, []) as $listener) {
            $listener = Reflector::instantiate($listener);
            if (method_exists($listener, 'handle')) {
                throw new \LogicException("Auth event listener must have 'handle(event)' method.");
            }
            $method = new \ReflectionMethod($listener, 'handle');
            $type   = Reflector::getTypeHint($method->getParameters()[0]);
            if (Reflector::typeOf($event, $type)) {
                $method->invoke($listener, $event);
            }
        }
    }
}

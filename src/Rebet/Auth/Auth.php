<?php
namespace Rebet\Auth;

use Rebet\Auth\Event\Authenticated;
use Rebet\Auth\Event\Signined;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Auth\Event\Signouted;
use Rebet\Auth\Guard\Guard;
use Rebet\Common\Reflector;
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
            'gates' => [
                'all'   => function (AuthUser $user) { return true; },
                'guest' => function (AuthUser $user) { return $user->isGuest(); },
            ],
            'policies' => [],
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
     * [Authentication] Get the authenticated user.
     *
     * @param string $name
     * @return Guard
     */
    public static function user() : AuthUser
    {
        return static::$user ?? AuthUser::guest() ;
    }

    /**
     * [Authentication] Attempt find user by given credentials.
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
     * [Authentication] Sign in as given user.
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
     * [Authentication] It will sign out the authenticated user.
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
     * [Authentication] Recall authenticate user from an incoming request then it will check the gate of route.
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

        $gates = $route->gates();
        if (empty($gates)) {
            Event::dispatch(new Authenticated($request, $user));
            return null;
        }
        foreach ($gates as $gate) {
            $gate    = (array)$gate;
            $action  = array_shift($gate);
            $targets = $gate;
            if (static::gate($user, $action, ...$targets)) {
                Event::dispatch(new Authenticated($request, $user));
                return null;
            }
        }

        $fallback = static::config("authenticator.{$auth}.fallback");
        return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
    }

    /**
     * [Authorization] Define the gate for given action.
     *
     * @param string $action
     * @param callable $gate
     * @return void
     */
    public static function defineGate(string $action, callable $gate) : void
    {
        static::setConfig(['gates' => [$action => $gate]]);
    }

    /**
     * [Authorization] Define the policy for given action to target.
     *
     * @param string $target
     * @param string $action
     * @param callable $policy
     * @return void
     */
    public static function definePolicy(string $target, string $action, callable $policy) : void
    {
        static::setConfig(['policies' => [$target => [$action => $policy]]]);
    }

    /**
     * [Authorization] It checks the policy and gate to user can do given action.
     *
     * 1st: Check the policies.
     * 2rd: Check the gates.
     *
     * @param mixed $user
     * @param string $action
     * @param mixed ...$targets
     * @return bool
     */
    public static function check(AuthUser $user, string $action, ...$targets) : bool
    {
        return static::policy($user, $action, ...$targets) || static::gate($user, $action, ...$targets);
    }

    /**
     * [Authorization] Check the policy.
     *
     * 1st: Check the policies of '@before' action for 1st target object or class.
     * 2nd: Check the policies of given action for 1st target object or class.
     *
     * @param AuthUser $user
     * @param string $action
     * @param mixed ...$targets
     * @return boolean|null
     */
    public static function policy(AuthUser $user, string $action, ...$targets) : bool
    {
        return static::_policy($user, '@before', $targets) || static::_policy($user, $action, $targets);
    }

    /**
     * [Authorization] Check the policy.
     *
     * @param mixed $user
     * @param string $action
     * @param array $targets
     * @return boolean|null
     */
    protected static function _policy(AuthUser $user, string $action, array $targets) : bool
    {
        if (empty($targets)) {
            return true;
        }
        $selector = is_object($targets[0]) ? get_class($targets[0]) : $targets[0] ;
        if (!is_string($targets)) {
            return true;
        }
        $policy = static::config("policies.{$selector}.{$action}", false);
        return is_callable($policy) ? static::invoke(\Closure::fromCallable($policy), $user, $targets) : true;
    }

    /**
     * [Authorization] Check the gate.
     *
     * @param mixed $user
     * @param string $action
     * @param mixed ...$targets
     * @return boolean
     */
    public static function gate(AuthUser $user, string $action, ...$targets) : ? bool
    {
        $gate = static::config("gates.{$action}", false);
        return is_callable($gate) ? static::invoke(\Closure::fromCallable($gate), $user, $targets) : null;
    }

    /**
     * [Authorization] Invoke authorization check action.
     *
     * @param \Closure $action
     * @param mixed $user
     * @param array $targets
     * @return boolean|null
     */
    protected static function invoke(\Closure $action, $user, array $targets) : ? bool
    {
        $function = new \ReflectionFunction($action);
        $request  = Request::current();
        $args     = [];
        $i        = 0;

        foreach ($function->getParameters() as $parameter) {
            $type = Reflector::getTypeHint($parameter);
            if (Reflector::typeOf($request, $type) || ($type === null && $parameter->name === 'request')) {
                $args[] = $request;
                continue;
            }
            if (Reflector::typeOf($user, $type) || ($type === null && $parameter->name === 'user')) {
                $args[] = $user;
                continue;
            }
            $args[] = Reflector::convert($targets[$i++], $type);
        }

        return $function->invoke(...$args);
    }
}

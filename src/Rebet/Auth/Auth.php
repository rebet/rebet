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
            'gates'    => [],
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

    /**
     * Define the gate for given action.
     *
     * @param string $action
     * @param callable $gate
     * @return void
     */
    public static function gate(string $action, callable $gate) : void
    {
        static::setConfig(['gates' => [$action => $gate]]);
    }

    /**
     * Define the policy for given action to target.
     *
     * @param string $target
     * @param string $action
     * @param callable $policy
     * @return void
     */
    public static function policy(string $target, string $action, callable $policy) : void
    {
        static::setConfig(['policies' => [$target => [$action => $policy]]]);
    }

    /**
     * It checks the user can do given action to targets.
     *
     * 1st: Check the policies of '@before' action for 1st target object or class.
     * 2nd: Check the policies of given action for 1st target object or class.
     * 3rd: Check the gates of given action.
     *
     * @param mixed $user
     * @param string $action
     * @param mixed ...$targets
     * @return bool
     */
    public static function allow($user, string $action, ...$targets) : bool
    {
        $selector = empty($targets) ? null : (is_object($targets[0]) ? get_class($targets[0]) : $targets[0]);
        $allow    = static::checkPolicy($user, $selector, '@before', $targets) ??
                    static::checkPolicy($user, $selector, $action, $targets) ??
                    static::checkGate($user, $action, $targets);
        if ($allow === null) {
            throw new \LogicException("Undefined gate/policy action {$action}" . ($selector ? " for {$selector}." : "."));
        }
        return $allow;
    }

    /**
     * Check the policy.
     *
     * @param mixed $user
     * @param mixed $selector
     * @param string $action
     * @param array $targets
     * @return boolean|null
     */
    protected static function checkPolicy($user, $selector, string $action, array $targets) : ? bool
    {
        if (!is_string($selector)) {
            return null;
        }
        $policy = static::config("policies.{$selector}.{$action}", false);
        return is_callable($policy) ? static::invoke(\Closure::fromCallable($policy), $user, $targets) : null;
    }

    /**
     * Check the gate.
     *
     * @param mixed $user
     * @param string $action
     * @param array $targets
     * @return boolean
     */
    protected static function checkGate($user, string $action, array $targets = []) : ? bool
    {
        $gate = static::config("gates.{$action}", false);
        return is_callable($gate) ? static::invoke(\Closure::fromCallable($gate), $user, $targets) : null;
    }

    /**
     * Invoke check action.
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

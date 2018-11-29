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
use Rebet\Translation\Trans;

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
            'roles'       => [
                'all'   => function (AuthUser $user) { return true; },
                'guest' => function (AuthUser $user) { return $user->isGuest(); },
            ],
            'policies'    => [],
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
        $user     = $provider->findByCredentials($credentials);
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
     * If the user who was guarded and redirected to sign in page will success sign in then replay the guarded request, otherwise go to given url.
     *
     * @param Request $request
     * @param AuthUser|null $user
     * @param string $fallback url when signin failed
     * @param string $goto url when signined (default: '/')
     * @param bool $remember (default: false)
     * @return Response
     */
    public static function signin(Request $request, ?AuthUser $user, string $fallback, string $goto = '/', bool $remember = false) : Response
    {
        if ($user === null || $user->isGuest()) {
            Event::dispatch(new SigninFailed($request));
            return Responder::redirect($fallback)
                    ->with($request->input())
                    ->errors(['signin' => [Trans::get('message.signin_failed')]])
                    ;
        }

        $user->guard()->signin($request, $user, $remember);
        static::$user = $user;
        Event::dispatch(new Signined($request, $user, $remember));
        return $request->replay('guarded_by_auth') ?? Responder::redirect($goto);
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
     * [Authentication] Recall authenticate user from an incoming request then it will check the role of route.
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
        if (empty($roles) || static::role($user, ...$roles)) {
            Event::dispatch(new Authenticated($request, $user));
            return null;
        }

        $request->saveAs('guarded_by_auth');

        $fallback = static::config("authenticator.{$auth}.fallback");
        return is_callable($fallback) ? $fallback($request) : Responder::redirect($fallback);
    }

    /**
     * [Authorization] Define the role for given name.
     *
     * @param string $name
     * @param callable $checker
     * @return void
     */
    public static function defineRole(string $name, callable $checker) : void
    {
        static::setConfig(['roles' => [$name => $checker]]);
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
     * [Authorization] Check the policy.
     *
     * 1st: Check the policies of '@before' action for target object or class.
     * 2nd: Check the policies of given action for target object or class.
     *
     * @param AuthUser $user
     * @param string $action
     * @param string|object $target
     * @param mixed ...$extras
     * @return boolean|null
     */
    public static function policy(AuthUser $user, string $action, $target, ...$extras) : bool
    {
        return static::_policy($user, '@before', $target, $extras) || static::_policy($user, $action, $target, $extras);
    }

    /**
     * [Authorization] Check the policy.
     *
     * @param mixed $user
     * @param string $action
     * @param string|object $target
     * @param array $extras (default: [])
     * @return boolean|null
     */
    protected static function _policy(AuthUser $user, string $action, $target, array $extras = []) : bool
    {
        if (empty($target)) {
            return true;
        }
        $selector = is_object($target) ? get_class($target) : $target ;
        if (!is_string($selector)) {
            return true;
        }
        $policy = static::config("policies.{$selector}.{$action}", false);
        return is_callable($policy) ? static::invoke(\Closure::fromCallable($policy), $user, $targets) : true;
    }

    /**
     * [Authorization] Check the user satisfies any the given role conditions.
     *
     * If the role name concatenated some roles using ':' like "role_a:role_b:role_c" then check the user satisfies all role_a, role_b and role_c.
     *
     * @param mixed $user
     * @param string ...$names
     * @return boolean
     */
    public static function role(AuthUser $user, string ...$names) : bool
    {
        if (empty($names)) {
            return true;
        }
        foreach ($names as $name) {
            if (static::_role($user, $name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * [Authorization] Check the role.
     *
     * If the role name concatenated some roles using ':' like "role_a:role_b:role_c" then check the user satisfies all role_a, role_b and role_c.
     *
     * @param mixed $user
     * @param string $name
     * @return boolean
     */
    protected static function _role(AuthUser $user, string $name) : bool
    {
        $names = explode(':', $name);
        foreach ($names as $name) {
            $checker = static::config("roles.{$name}", false);
            if (!is_callable($checker) || !static::invoke(\Closure::fromCallable($checker), $user)) {
                return false;
            }
        }
        return true;
    }

    /**
     * [Authorization] Invoke authorization check action.
     *
     * @param \Closure $action
     * @param mixed $user
     * @param array $targets (default: [])
     * @return boolean|null
     */
    protected static function invoke(\Closure $action, $user, array $targets = []) : ? bool
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

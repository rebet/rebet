<?php
namespace Rebet\Auth;

use Rebet\Auth\Event\Authenticated;
use Rebet\Auth\Event\AuthenticateFailed;
use Rebet\Auth\Event\Signined;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Auth\Event\Signouted;
use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Auth\Guard\Guard;
use Rebet\Auth\Guard\StatefulGuard;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Event\Event;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Namespaces;

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

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/auth.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'guards'    => [],
            'providers' => [],
            'roles'     => [],
            'policies'  => [],
        ];
    }

    /**
     * Auth providers
     *
     * @var AuthProvider[]
     */
    protected static $providers = [];

    /**
     * Auth guards
     *
     * @var Guard[]
     */
    protected static $guards = [];

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * [Authentication] Get the authenticated user.
     *
     * @param string $guard (default: null for active guard)
     * @return AuthUser
     */
    public static function user(?string $guard = null) : AuthUser
    {
        $guard = static::guard($guard ?? static::applicableGuard());
        return $guard ? $guard->user() : AuthUser::guest() ;
    }

    /**
     * [Authentication] Get authentication provider of given name.
     *
     * @param string $name
     * @return AuthProvider
     */
    public static function provider(string $name) : AuthProvider
    {
        return static::$providers[$name] ?? (static::$providers[$name] = static::configInstantiate("providers.{$name}")->name($name)) ;
    }

    /**
     * [Authentication] Get authentication guard of given name.
     *
     * @param string|null $name
     * @return Guard|null
     */
    public static function guard(?string $name) : ?Guard
    {
        if ($name === null) {
            return null;
        }
        return static::$guards[$name] ?? (static::$guards[$name] = static::configInstantiate("guards.{$name}"))->name($name) ;
    }

    /**
     * Crear all of guards and providers instance.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$providers = [];
        static::$guards    = [];
    }

    /**
     * Get applicable guard of this request route.
     *
     * @param Request|null $request (default: null for Request::current())
     * @return string|null
     */
    protected static function applicableGuard(?Request $request = null) : ?string
    {
        $request = $request ?? Request::current() ;
        return $request->route ? $request->route->guard() : null ;
    }

    /**
     * [Authentication] Attempt find user by given credentials.
     *
     * @param Request $request
     * @param mixed $signin_id
     * @param string $password
     * @param string|null $guard (default: guard of the route, if not set then use channel name)
     * @return AuthUser
     */
    public static function attempt(Request $request, $signin_id, string $password, ?string $guard = null) : AuthUser
    {
        $guard = static::guard($guard ?? static::applicableGuard($request));
        if ($guard === null) {
            return AuthUser::guest();
        }
        if (!($guard instanceof StatefulGuard)) {
            throw new AuthenticateException("Auth::attempt() supported only StatefulGuard, the given guard '{$guard->name()}' is not StatefulGuard.");
        }
        return $guard->attempt($signin_id, $password);
    }

    /**
     * [Authentication] Sign in as given user.
     * If the user who was guarded and redirected to sign in page will success sign in then replay the guarded request, otherwise go to given url.
     *
     * @param Request $request
     * @param AuthUser $user
     * @param string $backto url when signin failed
     * @param string $goto url when signined (default: '/')
     * @param bool $remember (default: false)
     * @return Response
     * @uses Event::dispatch SigninFailed when signin failed.
     * @uses Event::dispatch Signined when signin success.
     */
    public static function signin(Request $request, AuthUser $user, string $backto, string $goto = '/', bool $remember = false) : Response
    {
        if ($user->isGuest()) {
            Event::dispatch(new SigninFailed($request, $user->charengedSigninId()));
            return Responder::redirect($backto)
                    ->with($request->input())
                    ->errors(['signin' => [Translator::get('message.signin_failed')]])
                    ;
        }

        $guard = static::guard(static::applicableGuard($request));
        if (!($guard instanceof StatefulGuard)) {
            $name = $guard ? $guard->name() : 'null' ;
            throw new AuthenticateException("Auth::signin() supported only StatefulGuard, the authenticated user's guard '{$name}' is not StatefulGuard.");
        }

        $response = $guard->signin($user, $goto, $remember);
        Event::dispatch(new Signined($request, $user, $remember));
        return $response;
    }

    /**
     * [Authentication] It will sign out the authenticated user.
     *
     * @param Request $request
     * @param string $goto (default: '/')
     * @return Response
     * @uses Event::dispatch Signouted when signout.
     */
    public static function signout(Request $request, string $goto = '/') : Response
    {
        $guard = static::guard(static::applicableGuard($request));
        if (!$guard || $guard->user()->isGuest()) {
            return Responder::redirect($goto);
        }

        $signouted_user = $guard->user();
        $response       = $guard->signout($goto);
        Event::dispatch(new Signouted($request, $signouted_user));
        return $response;
    }

    /**
     * [Authentication] Recall authenticate user from an incoming request then it will check the role of route.
     *
     * @param Request $request
     * @return Response|null response when authenticate failed
     * @uses Event::dispatch Authenticated when authenticate success (exclude Guest user).
     * @uses Event::dispatch AuthenticateFailed when authenticate failed (exclude Guest user).
     */
    public static function authenticate(Request $request) : ?Response
    {
        $guard = static::guard(static::applicableGuard($request));
        if (!$guard) {
            return null;
        }
        if ($fallback = $guard->authenticate()) {
            Event::dispatch(new AuthenticateFailed($request, $guard->user()));
            return $fallback;
        }
        Event::dispatch(new Authenticated($request, $guard->user()));
        return null;
    }

    /**
     * [Authorization] Define the role for given name.
     *
     * @param string $name
     * @param callable $checker function([Request $request,] AuthUser $user):bool
     * @return void
     */
    public static function defineRole(string $name, callable $checker) : void
    {
        static::setConfig(['roles' => [$name => $checker]]);
    }

    /**
     * [Authorization] Define the before policy for given action to target.
     *
     * @param string $target class name
     * @param callable $policy function([Request $request,] AuthUser $user, TargetClass $target, ...$etras):bool
     * @return void
     */
    public static function defineBeforePolicy(string $target, callable $policy) : void
    {
        static::definePolicy($target, '@before', $policy);
    }

    /**
     * [Authorization] Define the policy for given action to target.
     *
     * @param string $target
     * @param string $action
     * @param callable $policy function([Request $request,] AuthUser $user, TargetClass $target, ...$etras):bool
     * @return void
     */
    public static function definePolicy(string $target, string $action, callable $policy) : void
    {
        static::setConfig(['policies' => [$target => [$action => $policy]]]);
    }

    /**
     * [Authorization] Check the policy.
     *
     * 1st: Check the policies of '@before' action for target object or class (prepend $action as first $extras argument).
     * 2nd: Check the policies of given action for target object or class.
     *
     * @param AuthUser $user
     * @param string $action
     * @param string|object $target can be use @ namespace alias
     * @param mixed ...$extras
     * @return boolean
     */
    public static function policy(AuthUser $user, string $action, $target, ...$extras) : bool
    {
        $target = Namespaces::resolve($target);
        return static::_policy($user, '@before', $target, array_merge([$action], $extras)) || static::_policy($user, $action, $target, $extras);
    }

    /**
     * [Authorization] Check the policy.
     *
     * @param mixed $user
     * @param string $action
     * @param string|object $target
     * @param array $extras (default: [])
     * @return boolean
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
        return is_callable($policy) ? static::invoke(\Closure::fromCallable($policy), $user, array_merge([$target], $extras)) : false ;
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
    protected static function invoke(\Closure $action, $user, array $targets = []) : ?bool
    {
        $function = new \ReflectionFunction($action);
        $request  = Request::current();
        $args     = [];
        $i        = 0;

        foreach ($function->getParameters() as $parameter) {
            $type = Reflector::getTypeHint($parameter);
            if (($type !== null && Reflector::typeOf($request, $type)) || ($type === null && $parameter->name === 'request')) {
                $args[] = $request;
                continue;
            }
            if (($type !== null && Reflector::typeOf($user, $type)) || ($type === null && $parameter->name === 'user')) {
                $args[] = $user;
                continue;
            }
            $args[] = Reflector::convert($targets[$i++], $type);
        }

        return $function->invoke(...$args);
    }
}

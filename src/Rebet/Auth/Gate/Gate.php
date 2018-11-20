<?php
namespace Rebet\Auth\Gate;

use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\Http\Request;

/**
 * Gate Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Gate
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'gates'    => [],
            'policies' => [],
        ];
    }
    
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Define the gate for given action.
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
     * Define the policy for given action to target.
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
        $selector = empty($targets) ? null : (is_object($targets[0]) ? get_class($targets[0]) : $targets[0]) ;
        $allow    = static::policy($user, $selector, '@before', $targets) ??
                    static::policy($user, $selector, $action, $targets) ??
                    static::gate($user, $action, $targets)
                    ;
        if ($allow === null) {
            throw new \LogicException("Undefined gate/policy action {$action}".($selector ? " for {$selector}." : "."));
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
    protected static function policy($user, $selector, string $action, array $targets) : ?bool
    {
        if (!is_string($selector)) {
            return null;
        }
        $policy = static::config("policies.{$selector}.{$action}", false);
        return is_callable($policy) ? static::invoke(\Closure::fromCallable($policy), $user, $targets) : null ;
    }

    /**
     * Check the gate.
     *
     * @param mixed $user
     * @param string $action
     * @param array $targets
     * @return boolean
     */
    protected static function gate($user, string $action, array $targets = []) : ?bool
    {
        $gate = static::config("gates.{$action}", false);
        return is_callable($gate) ? static::invoke(\Closure::fromCallable($gate), $user, $targets) : null ;
    }

    /**
     * Invoke check action.
     *
     * @param \Closure $action
     * @param mixed $user
     * @param array $targets
     * @return boolean|null
     */
    protected static function invoke(\Closure $action, $user, array $targets) : ?bool
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

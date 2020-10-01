<?php
namespace Rebet\Auth;

use Rebet\Auth\Guard\Guard;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Json;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Strings;
use Rebet\Tools\Config\Configurable;
use Rebet\Inflection\Inflector;

/**
 * Auth User Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AuthUser implements \JsonSerializable
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'guest_aliases'               => [],
            'aliases_max_recursion_depth' => 20,
        ];
    }

    /**
     * The authenticated user instance.
     *
     * @var mixed
     */
    protected $user = null;

    /**
     * The alias map to connect from the authenticated to user.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Sign-in ID when sign-in failed.
     *
     * @var mixed
     */
    protected $charenged_signin_id = null;

    /**
     * The authenticated guard instance.
     *
     * @var Guard
     */
    protected $guard = null;

    /**
     * The authenticated provider instance.
     *
     * @var AuthProvider
     */
    protected $provider = null;

    /**
     * The authenticator name of this user.
     *
     * @var string
     */
    public $authenticator;

    /**
     * Create a authenticated user instance.
     * If you want to handle multiple user sources uniformly, you can define aliases to make transparent access with common properties possible.
     * Aliases can be defined in the following format.
     *
     *  - string of the source field name.
     *    ex) 'email' => 'mail_address'
     *    Note: This alias can be set new value to origin source field via alias.
     *    Note: Aliases are resolved recursively.
     *          If you do not want this behavior, recursion can be suppressed by giving "!" at the beginning of the alias name.
     *          ex) 'email' => '!mail_address'
     *
     *  - string starts with '@' for fixed string value.
     *    ex) 'role' => '@ADMIN' (get 'ADMIN' value when access via $auth_user->role)
     *    Note: This alias can not be set new value via alias.
     *
     *  - callable to get the value from source.
     *    ex) 'name' => function($user) { return $user ? "{$user->first_name} {$user->last_name}" : 'Guest' ; }
     *    Note: This alias can not be set new value via alias.
     *    Note: Argument $user will be null when the user is guest.
     *
     *  - others for fixed value.
     *    ex) 'role' => 1
     *    Note: This alias can not be set new value via alias.
     *
     * Note that AuthUser must be able to access an identifier that uniquely identifies the user source given the property name "id".
     * If the "id" alias is not specified, this class generates a primary key alias from the class name of the given user source by Inflector.
     * (If the user source is null or an array, use "user_id" as it is defaultly.)
     *
     * @param mixed $user
     * @param array $aliases
     */
    public function __construct($user, array $aliases = [])
    {
        $this->user    = $user;
        $this->aliases = $aliases;
        if (!isset($this->aliases['id'])) {
            $this->aliases['id'] = $user === null || is_array($user) ? 'user_id' : Inflector::primarize((new \ReflectionClass($user))->getShortName());
        }
    }

    /**
     * Get the Guest user instance.
     *
     * @param mixed $charenged_signin_id when signin failed.
     * @param array $aliases (default: depend on configure)
     * @return self
     */
    public static function guest($charenged_signin_id = null, ?array $aliases = []) : self
    {
        $guest = new static(null, array_merge(static::config('guest_aliases', false, []), $aliases));

        $guest->charenged_signin_id = $charenged_signin_id;
        return $guest;
    }

    /**
     * Get and Set the Guard instance of this authenticated user.
     *
     * @return self|Guard|null
     */
    public function guard(?Guard $guard = null)
    {
        if ($guard === null) {
            return $this->guard;
        }
        $this->guard = $guard;
        return $this;
    }

    /**
     * Get and Set the AuthProvider instance of this authenticated user.
     *
     * @return self|AuthProvider|null
     */
    public function provider(?AuthProvider $provider = null)
    {
        if ($provider === null) {
            return $this->provider;
        }
        $this->provider = $provider;
        return $this;
    }

    /**
     * Get alias name of given name.
     *
     * @param string $name
     * @return mixed
     */
    protected function alias(string $name)
    {
        $max   = static::config('aliases_max_recursion_depth');
        $depth = 0;
        $alias = $name ;
        while (isset($this->aliases[$alias])) {
            $alias = $this->aliases[$alias];
            if (!is_string($alias)) {
                break;
            }
            if (Strings::startsWith($alias, '!')) {
                $alias = Strings::lcut($alias, 1);
                break;
            }
            if ($max < ++$depth) {
                throw new LogicException("Too many (over {$max}) aliases recursion depth.");
            }
        }
        return $alias;
    }

    /**
     * Get the property of given name from authenticated user using alias map.
     *
     * @param string $name
     * @param mixed $default (default: null)
     * @return mixed
     */
    protected function get(string $name, $default = null)
    {
        $alias = $this->alias($name);
        if ($alias instanceof \Closure) {
            return $alias($this->user);
        }
        if (Strings::startsWith($alias, '@')) {
            return Strings::lcut($alias, 1);
        }
        if (!is_string($alias)) {
            return $alias ?? $default;
        }
        return Reflector::get($this->user, $alias, $default);
    }

    /**
     * Refresh authenticated user data from provider.
     *
     * @return self
     */
    public function refresh() : self
    {
        if ($this->provider) {
            $user       = $this->provider->findById($this->id);
            $this->user = $user ? $user->raw() : $this->user ;
        }
        return $this;
    }

    /**
     * It checks the authenticated user is guest.
     *
     * @return boolean
     */
    public function isGuest() : bool
    {
        return $this->user === null;
    }

    /**
     * Get the Sign-in ID when sign-in failed.
     *
     * @return mixed
     */
    public function charengedSigninId()
    {
        return $this->charenged_signin_id;
    }

    /**
     * It checks the user is in the given roles.
     * If the role name concatenated some roles using ':' like "role_a:role_b:role_c" then check the user satisfies all role_a, role_b and role_c.
     *
     * @param string ...$roles
     * @return boolean
     */
    public function is(string ...$roles) : bool
    {
        return Auth::role($this, ...$roles);
    }

    /**
     * It checks the user is not in the given roles.
     * If the role name concatenated some roles using ':' like "role_a:role_b:role_c" then check the user satisfies all role_a, role_b and role_c.
     *
     * @param string ...$roles
     * @return boolean
     */
    public function isnot(string ...$roles) : bool
    {
        return !$this->is(...$roles);
    }

    /**
     * It checks the user can do given action to target.
     *
     * @param string $action
     * @param string|object $target
     * @param mixed ...$extras
     * @return boolean
     */
    public function can(string $action, $target, ...$extras) : bool
    {
        return Auth::policy($this, $action, $target, ...$extras);
    }

    /**
     * It checks the user can not do given action to target.
     *
     * @param string $action
     * @param string|object $target
     * @param mixed ...$extras
     * @return boolean
     */
    public function cannot(string $action, $target, ...$extras) : bool
    {
        return !$this->can($action, $target, ...$extras);
    }

    /**
     * Get the raw user data.
     *
     * @return mixed
     */
    public function &raw()
    {
        return $this->user;
    }

    /**
     * Dynamically access the user's attributes.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Property set accessor.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        if ($this->user === null) {
            return;
        }
        $alias = $this->alias($key);
        if (is_string($alias) && !Strings::startsWith($alias, '@')) {
            Reflector::set($this->user, $alias, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return Reflector::convert($this->user, 'string') ?? json_encode($this) ;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return Json::serialize($this->user);
    }
}

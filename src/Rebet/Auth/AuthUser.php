<?php
namespace Rebet\Auth;

use Rebet\Auth\Guard\Guard;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;
use Rebet\Inflection\Inflector;

/**
 * Auth User Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AuthUser
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'guest_aliases' => [],
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
     *    ex) 'email' => 'mail_addres'
     *
     *  - string starts with '@' for fixed string value.
     *    ex) 'role' => '@ADMIN' (get 'ADMIN' value when access via $auth_user->role)
     *
     *  - callable to get the value from source.
     *    ex) 'name' => function($user) { return $user ? "{$user->first_name} {$user->last_name}" : null ; }
     *    Note: Argument $user will be null when the user is guest.
     *
     *  - others for fixed value.
     *    ex) 'role' => 1
     *
     * Note that AuthUser must be able to access an identifier that uniquely identifies the user source given the property name "id".
     * If the "id" alias is not specified, this class generates a primary key alias from the class name of the given user source by Inflector.
     * (However, if the user source is an array, use "id" as it is defaultly.)
     *
     * @param mixed $user
     * @param array $aliases
     */
    public function __construct($user, array $aliases = [])
    {
        $this->user    = $user;
        $this->aliases = $aliases;
        if ($user && !isset($this->aliases['id'])) {
            $this->aliases['id'] = is_array($user) ? 'id' : Inflector::primarize((new \ReflectionClass($user))->getShortName());
        }
    }

    /**
     * Get the Guest user instance.
     *
     * @param array $aliases (default: depend on configure)
     * @return self
     */
    public static function guest(?array $aliases = null) : self
    {
        return new static(null, $aliases ?? static::config('guest_aliases', false, []));
    }

    /**
     * Get and Set the Guard instance of this authenticated user.
     *
     * @return Guard|null
     */
    public function guard(?Guard $guard = null) : ?Guard
    {
        return $guard === null ? $this->guard : $this->guard = $guard ;
    }

    /**
     * Get and Set the AuthProvider instance of this authenticated user.
     *
     * @return AuthProvider|null
     */
    public function provider(?AuthProvider $provider = null) : ?AuthProvider
    {
        return $provider === null ? $this->provider : $this->provider = $provider ;
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
        $alias = $this->aliases[$name] ?? $name ;
        if (Strings::startsWith($alias, '@')) {
            return Strings::ltrim($alias, '@');
        }
        if ($alias instanceof \Closure) {
            return $alias($this->user);
        }
        if (!is_string($alias)) {
            return $alias ?? $default;
        }
        return Reflector::get($this->user, $alias, $default);
    }

    /**
     * Reload authenticated user data.
     *
     * @return void
     */
    public function reload() : void
    {
        if ($this->provider) {
            $this->user = $this->provider->findById($this->id());
        }
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
    public function raw()
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
}

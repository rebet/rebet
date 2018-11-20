<?php
namespace Rebet\Auth;

use Rebet\Auth\Guard\Guard;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
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
    protected $alias = [];

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
     *
     * @param mixed $user
     * @param string|callable|null $id alias name or function($user){} to return id (default: create table_name_id from given user class name by Inflector)
     */
    public function __construct($user, $id = null)
    {
        $this->user  = $user;
        $this->alias = [
            'id' => $id ?? ($user ? Inflector::primarize((new \ReflectionClass($user))->getShortName()) : null),
        ];
    }

    /**
     * Get the Guest user instance.
     *
     * @return self
     */
    public static function guest() : self
    {
        return new static(null);
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
     * If the alias starts with '@' then return alias name without '@'.
     *
     * @param string $name
     * @param mixed $default (default: null)
     * @return mixed
     */
    protected function get(string $name, $default = null)
    {
        $alias = $this->alias[$name] ?? $name ;
        if (Strings::startsWith($alias, '@')) {
            return Strings::ltrim($alias, '@');
        }
        if ($alias instanceof \Closure) {
            return $alias($this->user);
        }
        return Reflector::get($this->user, $alias, $default);
    }

    /**
     * Get the id of this authenticated.
     *
     * @return mixed
     */
    public function id()
    {
        return $this->get('id');
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
     * It checks the authenticated user is GUEST.
     *
     * @return boolean
     */
    public function isGuest() : bool
    {
        return $this->user === null;
    }

    /**
     * It checks the user can do given action to targets.
     *
     * @param string $action
     * @param mixed ...$targets
     * @return boolean
     */
    public function can(string $action, ...$targets) : bool
    {
        return Auth::allow($this, $action, ...$targets);
    }

    /**
     * It checks the user can not do given action to targets.
     *
     * @param string $action
     * @param mixed ...$targets
     * @return boolean
     */
    public function cannot(string $action, ...$targets) : bool
    {
        return !$this->can($action, ...$targets);
    }

    /**
     * Dynamically access the user's attributes.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return Reflector::get($this->user, $key);
    }
}

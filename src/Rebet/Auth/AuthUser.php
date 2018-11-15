<?php
namespace Rebet\Auth;

use Rebet\Auth\Guard\Guard;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;

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
     * @param AuthProvider $provider
     * @param mixed $user
     * @param array $alias [
     *      'id'        => <$user's id        field or callback function($user){} to return id>,
     *      'signin_id' => <$user's signin_id field or callback function($user){} to return signin_id>,
     *      'role'      => <$user's role      field or callback function($user){} to return role or @ROLE_NAME>,
     * ] (default" [])
     */
    public function __construct(?Guard $guard, ?AuthProvider $provider, $user, array $alias = [])
    {
        $this->guard    = $guard;
        $this->provider = $provider;
        $this->user     = $user;
        $this->alias    = $alias;
    }

    /**
     * Get the Guest user instance.
     *
     * @return self
     */
    public static function guest() : self
    {
        return new static(null, null, null, ['role' => '@GUEST']);
    }

    /**
     * Get the Guard instance of this authenticated user.
     *
     * @return Guard|null
     */
    public function guard() : ?Guard
    {
        return $this->guard;
    }

    /**
     * Get the AuthProvider instance of this authenticated user.
     *
     * @return AuthProvider|null
     */
    public function provider() : ?AuthProvider
    {
        return $this->provider;
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
     * Get the role of this authenticated.
     * If role is nothing then return 'GUEST'.
     *
     * @return string
     */
    public function role() : string
    {
        return $this->get('role', 'GUEST');
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
     * Get the signin id of this authenticated.
     *
     * @return mixed
     */
    public function signinId()
    {
        return $this->get('signin_id');
    }

    /**
     * Check the authenticated user's role is in given roles.
     *
     * @param string ...$roles
     * @return boolean
     */
    public function in(string ...$roles) : bool
    {
        return in_array($this->role(), $roles, true);
    }

    /**
     * Check the authenticated user's role is not in given roles.
     *
     * @param string ...$roles
     * @return boolean
     */
    public function notIn(string ...$roles) : bool
    {
        return !static::in(...$roles);
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
     * It checks the authenticated user role is GUEST.
     *
     * @return boolean
     */
    public function isGuest() : bool
    {
        return $this->role() === "GUEST";
    }

    /**
     * It checks the authenticated user role is USER.
     *
     * @return boolean
     */
    public function isUser() : bool
    {
        return $this->role() === "USER";
    }

    /**
     * It checks the authenticated user role is ADMIN.
     *
     * @return boolean
     */
    public function isAdmin() : bool
    {
        return $this->role() === "ADMIN";
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
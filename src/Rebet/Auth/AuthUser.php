<?php
namespace Rebet\Auth;

use Rebet\Auth\Provider\AuthProvider;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Enum\Enum;


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
     * The authenticated provider instance.
     *
     * @var AuthProvider
     */
    protected $provider = null;

    /**
     * Create a authenticated user instance.
     *
     * @param AuthProvider $provider
     * @param mixed $user
     * @param array $alias [
     *      'id'   => <$user's id   field>,
     *      'role' => <$user's role field or @ROLE_NAME>,
     * ] (default" [])
     */
    public function __construct(AuthProvider $provider, $user, array $alias = [])
    {
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
        return new static(null, null, ['role' => '@GUEST']);
    }

    /**
     * Get the property of given name from authenticated user using alias map.
     * If the alias starts with '@' then return alias name without '@'.
     *
     * @param string $name
     * @param mixed $default (default: null)
     * @return mixed
     */
    protected function get(string $name, $default = null) {
        $alias = $this->alias[$name] ?? $name ;
        if(Strings::startsWith($alias, '@')) {
            return Strings::ltrim($alias, '@');
        }
        $value = Reflector::get($this->user, $alias, $default);
        if($value instanceof Enum) {
            $value = $value->name ?? $value->label ;
        }
        return $value;
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
     * Reload authenticated user data.
     *
     * @return void
     */
    public function reload() : void 
    {
        if($this->provider) {
            $this->user = $this->provider->findById($this->id());
        }
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

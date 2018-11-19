<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Common\Collection;
use Rebet\Common\Securities;
use Rebet\Config\Configurable;

/**
 * Array Auth Provider Class
 *
 * This is readonly provider depend on configure.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArrayProvider extends AuthProvider
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'hasher' => function ($password) { return Securities::hash($password); },
        ];
    }

    /**
     * Users map data.
     *
     * @var array
     */
    protected $users = [];

    /**
     * Alias map data.
     *
     * @var array
     */
    protected $alias = [];

    /**
     * Password hasher of this provider.
     *
     * @var \Closure
     */
    protected $hasher = null;

    /**
     * Create a readonly array provider.
     * Users information must have the following data,
     *
     *  - 'id'       => id
     *  - 'role'     => role ('USER', 'ADMIN', etc)
     *  - 'email'    => email address   (for credentials)
     *  - 'password' => hashed password (for credentials)
     *
     * And if you want to add other information, you can add attribute to users record.
     *
     * @param array $users
     * @param array $alias (default: ['signin_id' => 'email'])
     * @param \Closure|null $hasher (default: depend on configure)
     */
    public function __construct(?array $users, array $alias = ['signin_id' => 'email'], \Closure $hasher = null)
    {
        $this->users  = Collection::valueOf($users);
        $this->alias  = $alias;
        $this->hasher = $hasher ?? static::config('hasher');
    }

    /**
     * Find user by id.
     *
     * @param mixed $id
     * @return AuthUser|null
     */
    public function findById($id) : ?AuthUser
    {
        $user = $this->users->first(function ($user) use ($id) { return $user['id'] == $id; });
        if ($user) {
            return new AuthUser($user);
        }
        return null;
    }

    /**
     * Find user by signin_id.
     * The signin_id may be a login ID, a email address or member number, but it must be unique.
     *
     * @param mixed $credentials ['signin_id' => id, 'password' => password]
     * @return AuthUser|null
     */
    public function findByCredentials(array $credentials) : ?AuthUser
    {
        $alias = $this->alias;
        $users = $this->users;
        foreach ($credentials as $key => $value) {
            $value = $key === 'password' ? $this->hasher($value) : $value ;
            $users = $users->filter(function ($user) use ($key, $value, $alias) {
                $key = $alias[$key] ?? $key;
                return isset($user[$key]) && $user[$key] == $value;
            });
        }

        return $users->count() === 1 ? new AuthUser($users->first()) : null ;
    }
}

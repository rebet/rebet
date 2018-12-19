<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Config\Configurable;
use Rebet\Stream\Stream;

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
            'signin_id_name' => 'email',
            'precondition'   => function ($user) { return true; },
        ];
    }

    /**
     * Users map data.
     *
     * @var Stream
     */
    protected $users = [];

    /**
     * Sign in id attribute name
     *
     * @var string
     */
    protected $signin_id_name = null;

    /**
     * Preconditions for signin id authenticate.
     *
     * @var callable
     */
    protected $precondition = null;

    /**
     * Create a readonly array provider.
     * Users information must have the following data,
     *
     *  - 'id'       => id
     *  - 'role'     => role ('user', 'admin', etc)
     *  - 'email'    => email address   (for credentials)
     *  - 'password' => hashed password (for credentials)
     *
     * And if you want to add other information, you can add attribute to users record.
     *
     * @param array $users
     * @param string|null $signin_id_name (default: depend on configure)
     * @param callable|null $precondition (default: depend on configure)
     */
    public function __construct(array $users, ?string $signin_id_name = null, ?callable $precondition = null)
    {
        $this->users          = Stream::of($users, true);
        $this->signin_id_name = $signin_id_name ?? static::config('signin_id_name') ;
        $this->precondition   = $precondition ?? static::config('precondition');
    }

    /**
     * Find user by id.
     *
     * @param mixed $id
     * @return AuthUser|null
     */
    public function findById($id) : ?AuthUser
    {
        return $this->users
            ->first(function ($user) use ($id) { return $user['id'] == $id; })
            ->return(function ($user) { return new AuthUser($user); });
    }

    /**
     * {@inheritDoc}
     */
    protected function findBySigninId($signin_id, $precondition = null) : ?AuthUser
    {
        return $this->users
            ->where(function ($user) use ($signin_id) { return $user[$this->signin_id_name] == $signin_id; })
            ->where($precondition)
            ->first()
            ->return(function ($user) { return new AuthUser($user); });
    }

    /**
     * {@inheritDoc}
     */
    public function rehashPassword($id, string $new_hash) : void
    {
        // Nothing to do (Password rehash not supported)
    }
}

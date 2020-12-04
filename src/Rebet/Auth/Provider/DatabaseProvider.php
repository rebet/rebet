<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Database\Dao;
use Rebet\Tools\Config\Configurable;

/**
 * Database Auth Provider Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DatabaseProvider extends AuthProvider
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'signin_id_name' => 'email',
            'password_name'  => 'password',
        ];
    }

    /**
     * Database name defined by Dao.dbs.
     *
     * @var string|null
     */
    protected $db = null;

    /**
     * Entity class name.
     *
     * @var string
     */
    protected $entity;

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $alises = [];

    /**
     * Sign in id attribute name
     *
     * @var string
     */
    protected $signin_id_name = null;

    /**
     * Password attribute name
     *
     * @var string
     */
    protected $password_name = null;

    /**
     * Preconditions for signin id authenticate.
     *
     * @var callable
     */
    protected $precondition = null;

    /**
     * Create a database provider.
     *
     * @param string $entity class name that extended Entity class
     * @param string $signin_id_name (default: 'email')
     * @param string $password_name (default: 'password')
     * @param array $precondition for ransack conditions (default: [])
     * @param array $alises for AuthUser (default: [])
     * @param string|null $db name configured Dao.dbs (default: null for default database)
     */
    public function __construct(string $entity, string $signin_id_name = 'email', string $password_name = 'password', array $precondition = [], array $alises = [], ?string $db = null)
    {
        $this->entity         = $entity;
        $this->signin_id_name = $signin_id_name ?? static::config('signin_id_name') ;
        $this->password_name  = $password_name ?? static::config('password_name') ;
        $this->alises         = $alises;
        $this->precondition   = $precondition;
        $this->db             = $db;
    }

    /**
     * {@inheritDoc}
     */
    public function findById($id) : ?AuthUser
    {
        return ($user = $this->entity::find($id, false, $this->db)) ? new AuthUser($user, $this->alises) : null ;
    }

    /**
     * {@inheritDoc}
     */
    public function findByToken(string $token_name, ?string $token, $precondition = null) : ?AuthUser
    {
        return new AuthUser($this->entity::findBy(array_merge($precondition ?? $this->precondition, [$token_name => $token]), false, $this->db));
    }

    /**
     * {@inheritDoc}
     */
    protected function findBySigninId($signin_id, $precondition = null) : ?AuthUser
    {
        return new AuthUser($this->entity::findBy(array_merge($precondition ?? $this->precondition, [$this->signin_id_name => $signin_id]), false, $this->db));
    }

    /**
     * {@inheritDoc}
     */
    public function rehashPassword($id, string $new_hash) : void
    {
        $this->entity::updates(
            [$this->password_name => $new_hash],
            array_combine($this->entity::primaryKeys(), (array)$id),
            null,
            $this->db
        );
    }
}

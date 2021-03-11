<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\Entity\RememberToken;
use Rebet\Tools\DateTime\DateTime;

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
     * Aliases for AuthUser who provided by this provider.
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
     * API token attribute name
     *
     * @var string
     */
    protected $api_token_name = null;

    /**
     * Preconditions for signin id authenticate.
     *
     * @var callable
     */
    protected $precondition = null;

    /**
     * remember_me table expired data clean rate.
     * NOTE: If you set null then never cleaned. In such a case you can call RememberToken::deleteExpired() in your batch by crond.
     *
     * @var int|null
     */
    protected $expired_remember_token_clean_rate;

    /**
     * Create a database provider.
     *
     * @param string $entity class name that extended Entity class
     * @param string $signin_id_name (default: 'email')
     * @param string $password_name (default: 'password')
     * @param string $api_token_name (default: 'api_token')
     * @param int|null $expired_remember_token_clean_rate (default: 100)
     * @param array $precondition for ransack conditions (default: [])
     * @param array $alises for AuthUser who provided by this provider. (default: [])
     * @param string|null $db name configured Dao.dbs (default: null for default database)
     */
    public function __construct(
        string $entity,
        string $signin_id_name                  = 'email',
        string $password_name                   = 'password',
        string $api_token_name                  = 'api_token',
        ?int $expired_remember_token_clean_rate = 100,
        array $precondition                     = [],
        array $alises                           = [],
        ?string $db                             = null
    ) {
        $this->entity                            = $entity;
        $this->signin_id_name                    = $signin_id_name;
        $this->password_name                     = $password_name;
        $this->api_token_name                    = $api_token_name;
        $this->expired_remember_token_clean_rate = $expired_remember_token_clean_rate;
        $this->alises                            = $alises;
        $this->precondition                      = $precondition;
        $this->db                                = $db;
    }

    /**
     * {@inheritDoc}
     */
    public function findById($id) : ?AuthUser
    {
        if ($id === null) {
            return null;
        }
        $user = $this->entity::find($id, false, $this->db);
        return $user ? new AuthUser($user, $this->alises, $this) : null ;
    }

    /**
     * {@inheritDoc}
     */
    public function findByToken(?string $token, $precondition = null) : ?AuthUser
    {
        if ($token === null) {
            return null;
        }
        $user = $this->entity::findBy(array_merge($precondition ?? $this->precondition, [$this->api_token_name => $this->hashToken($token)]), false, $this->db);
        return $user ? new AuthUser($user, $this->alises, $this) : null ;
    }

    /**
     * {@inheritDoc}
     */
    protected function findBySigninId($signin_id, $precondition = null) : ?AuthUser
    {
        if ($signin_id === null) {
            return null;
        }
        $user = $this->entity::findBy(array_merge($precondition ?? $this->precondition, [$this->signin_id_name => $signin_id]), false, $this->db);
        return $user ? new AuthUser($user, $this->alises, $this) : null ;
    }

    /**
     * {@inheritDoc}
     */
    public function supportRememberToken() : bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function findByRememberToken(?string $token) : ?AuthUser
    {
        if ($token === null) {
            return null;
        }
        $remember_me = RememberToken::findBy(['provider' => $this->name, 'remember_token' => $this->hashToken($token), 'expires_at_after' => DateTime::now()], false, $this->db);
        return $remember_me ? $this->findById($remember_me->remember_id) : null ;
    }

    /**
     * {@inheritDoc}
     */
    public function issuingRememberToken($id, int $remember_days) : ?string
    {
        $now                      = DateTime::now();
        $remember_token           = new RememberToken();
        $remember_token->provider = $this->name;
        do {
            $remember_token->remember_token = $this->hashToken($plain_token = $this->generateToken());
        } while ($remember_token->exists($this->db));
        $remember_token->remember_id = $id;
        $remember_token->expires_at  = $now->addDay($remember_days);
        $remember_token->create($now, $this->db);

        return $plain_token;
    }

    /**
     * {@inheritDoc}
     */
    public function removeRememberToken(?string $token) : void
    {
        RememberToken::deleteBy(['provider' => $this->name, 'remember_token' => $this->hashToken($token)], $this->db);
        if ($this->expired_remember_token_clean_rate && random_int(1, $this->expired_remember_token_clean_rate) === 1) {
            RememberToken::deleteExpired();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rehashPassword($id, string $new_hash) : void
    {
        $this->entity::updateBy(
            [$this->password_name => $new_hash],
            array_combine($this->entity::primaryKeys(), (array)$id),
            null,
            $this->db
        );
    }
}

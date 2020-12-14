<?php
namespace Rebet\Auth\Provider\Entity;

use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\DateTime;

/**
 * Remember Token Entity Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RememberToken extends Entity
{
    /**
     * Auth provider name that created this remember token.
     *
     * @var string
     * @PrimaryKey
     */
    public $provider;

    /**
     * Remember token value.
     *
     * @var string
     * @PrimaryKey
     */
    public $remember_token;

    /**
     * ID that remembered by this remember token's provider.
     *
     * @var string|int
     */
    public $remember_id;

    /**
     * Token expired date time.
     *
     * @var DateTime
     * @PhpType(DateTime::class)
     */
    public $expires_at;

    /**
     * @var DateTime
     * @PhpType(DateTime::class)
     */
    public $created_at;

    /**
     * @var DateTime
     * @PhpType(DateTime::class)
     */
    public $updated_at;

    /**
     * Delete all expoired remember tokens.
     *
     * @return int
     */
    public static function deleteExpired() : int
    {
        return static::deleteBy(['expires_at_before' => DateTime::now()]);
    }

    /**
     * Delete remember tokens by given remember ID.
     *
     * @param string $provider
     * @param mixed $remember_id
     * @return int
     */
    public static function deleteByUser(string $provider, $remember_id) : int
    {
        return static::deleteBy(['provider' => $provider, 'remember_id' => $remember_id]);
    }
}

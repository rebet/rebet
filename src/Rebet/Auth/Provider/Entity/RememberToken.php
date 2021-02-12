<?php
namespace Rebet\Auth\Provider\Entity;

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
     * @PrimaryKey
     */
    public ?string $provider = null;

    /**
     * Remember token value.
     *
     * @PrimaryKey
     */
    public ?string $remember_token = null;

    /**
     * ID that remembered by this remember token's provider.
     */
    public ?string $remember_id = null;

    /**
     * Token expired date time.
     */
    public ?DateTime $expires_at = null;

    public ?DateTime $created_at = null;
    public ?DateTime $updated_at = null;

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

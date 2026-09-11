<?php
declare(strict_types=1);

namespace App\Model;

use App\Enum\Gender;
use Rebet\Database\Attribute\Defaults;
use Rebet\Database\Attribute\PrimaryKey;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;

/**
 * User Entity Class For {! $code_name !} Application
 *
 * A sample Entity that maps to the `users` table.
 * This is also used as the default Auth entity (see `auth.php` configuration), so it is set up
 * with the columns needed for authentication (`password`, `api_token`) as well as basic profile
 * columns.
 *
 * NOTE: This is just a sample, so please freely add, remove, or rename these properties
 *       (or even this class itself) to fit your application's needs.
 */
class User extends Entity
{
    /**
     * User ID (primary key, auto increment).
     *
     * @var int|null
     */
    #[PrimaryKey]
    public $user_id;

    /**
     * User name.
     *
     * @var string
     */
    public $name;

    /**
     * User gender.
     *
     * @var Gender|null
     */
    public Gender|null $gender = null;

    /**
     * User birthday.
     *
     * @var Date|null
     */
    public Date|null $birthday = null;

    /**
     * User email address.
     *
     * @var string
     */
    public $email;

    /**
     * User role name used for authorization (see `auth.php` configuration).
     *
     * @var string
     */
    #[Defaults("user")]
    public $role;

    /**
     * Hashed password (see `Rebet\Auth\Password`).
     *
     * @var string
     */
    public $password;

    /**
     * API token used for token based authentication (see `auth.php` configuration).
     *
     * @var string|null
     */
    public $api_token;

    /**
     * Data create timestamp.
     *
     * @var DateTime|null
     */
    public DateTime|null $created_at = null;

    /**
     * Data update timestamp.
     *
     * @var DateTime|null
     */
    public DateTime|null $updated_at = null;

    /**
     * Get the age calculated from the birthday.
     *
     * @return int|null
     */
    public function age() : int|null
    {
        return $this->birthday ? Date::valueOf($this->birthday)->age() : null ;
    }
}

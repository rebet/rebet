<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\DataModel\Entity;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;

class User extends Entity
{
    public $user_id;

    public $name;

    /**
     * @PhpType(Gender::class)
     */
    public $gender;

    /**
     * @PhpType(Date::class)
     */
    public $birthday;

    public $email;

    /**
     * @Defaults("user")
     */
    public $role;

    public $password;

    public $api_token;

    /**
     * @PhpType(DateTime::class)
     */
    public $created_at;

    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;

    /**
     * @Unmap
     */
    public $unmap;

    public function age() : ?int
    {
        return $this->birthday ? Date::valueOf($this->birthday)->age() : null ;
    }

    public function fortune(bool $for_update = false, bool $eager_load = true) : ?Fortune
    {
        return parent::belongsTo(Fortune::class, [], $for_update, $eager_load);
    }

    public function bank(bool $for_update = false, bool $eager_load = true) : ?Bank
    {
        return parent::hasOne(Bank::class, [], $for_update, $eager_load);
    }

    public function articles($ransack = [], $order_by = null, ?int $limit = null, bool $for_update = false, bool $eager_load = true) : array
    {
        return parent::hasMany(Article::class, [], $ransack, $order_by, $limit, $for_update, $eager_load);
    }

    /**
     * Method for unit test
     */
    public function belongsTo(string $class, array $alias = [], bool $for_update = false, bool $eager_load = true, ?string $name = null)
    {
        return parent::belongsTo($class, $alias, $for_update, $eager_load, $name ?? Reflector::caller());
    }

    /**
     * Method for unit test
     */
    public function hasOne(string $class, array $alias = [], bool $for_update = false, bool $eager_load = true, ?string $name = null)
    {
        return parent::hasOne($class, $alias, $for_update, $eager_load, $name ?? Reflector::caller());
    }

    /**
     * Method for unit test
     */
    public function hasMany(string $class, array $alias = [], array $ransacks = [], $order_by = null, ?int $limit = null, bool $for_update = false, bool $eager_load = true, ?string $name = null) : array
    {
        return parent::hasMany($class, $alias, $ransacks, $order_by, $limit, $for_update, $eager_load, $name ?? Reflector::caller());
    }
}

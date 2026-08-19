<?php
namespace App\Model;

use App\Enum\Gender;
use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;

class User extends Entity
{
    public $user_id;
    public $name;
    public Gender|null $gender = null;
    public Date|null $birthday = null;
    public $email;

    /**
     * @Defaults("user")
     */
    public $role;
    public $password;
    public $api_token;
    public DateTime|null $created_at = null;
    public DateTime|null $updated_at = null;

    /**
     * @Unmap
     */
    public $unmap;

    public function age() : int|null
    {
        return $this->birthday ? Date::valueOf($this->birthday)->age() : null ;
    }

    public function fortune(bool $for_update = false, bool $eager_load = true) : Fortune|null
    {
        return parent::belongsTo(Fortune::class, [], $for_update, $eager_load);
    }

    public function bank(bool $for_update = false, bool $eager_load = true) : Bank|null
    {
        return parent::hasOne(Bank::class, [], $for_update, $eager_load);
    }

    public function articles($ransack = [], $order_by = null, int|null $limit = null, bool $for_update = false, bool $eager_load = true) : array
    {
        return parent::hasMany(Article::class, [], $ransack, $order_by, $limit, $for_update, $eager_load);
    }

    /**
     * Method for unit test
     */
    public function belongsTo(string $class, array $alias = [], bool $for_update = false, bool $eager_load = true, string|null $name = null)
    {
        return parent::belongsTo($class, $alias, $for_update, $eager_load, $name ?? Reflector::caller());
    }

    /**
     * Method for unit test
     */
    public function hasOne(string $class, array $alias = [], bool $for_update = false, bool $eager_load = true, string|null $name = null)
    {
        return parent::hasOne($class, $alias, $for_update, $eager_load, $name ?? Reflector::caller());
    }

    /**
     * Method for unit test
     */
    public function hasMany(string $class, array $alias = [], array $ransacks = [], $order_by = null, int|null $limit = null, bool $for_update = false, bool $eager_load = true, string|null $name = null) : array
    {
        return parent::hasMany($class, $alias, $ransacks, $order_by, $limit, $for_update, $eager_load, $name ?? Reflector::caller());
    }
}

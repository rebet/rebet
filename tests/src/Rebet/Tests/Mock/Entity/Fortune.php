<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Tools\Reflection\Reflector;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;

class Fortune extends Entity
{
    /**
     * @PrimaryKey
     * @PhpType(Gender::class)
     */
    public $gender;

    /**
     * @PrimaryKey
     * @PhpType(Date::class)
     */
    public $birthday;

    public $result;

    /**
     * @PhpType(DateTime::class)
     */
    public $created_at;

    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;

    public function users($ransack = [], ?int $limit = null, bool $for_update = false, bool $eager_load = true) : array
    {
        return parent::hasMany(User::class, [], $ransack, null, $limit, $for_update, $eager_load);
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

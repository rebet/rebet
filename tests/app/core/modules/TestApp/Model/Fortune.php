<?php
namespace App\Model;

use App\Enum\Gender;
use Rebet\Database\Attribute\PrimaryKey;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;

class Fortune extends Entity
{
    #[PrimaryKey]
    public Gender|null $gender = null;

    #[PrimaryKey]
    public Date|null $birthday = null;

    public $result;

    public DateTime|null $created_at = null;
    public DateTime|null $updated_at = null;

    public function users($ransack = [], int|null $limit = null, bool $for_update = false, bool $eager_load = true) : array
    {
        return parent::hasMany(User::class, [], $ransack, null, $limit, $for_update, $eager_load);
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

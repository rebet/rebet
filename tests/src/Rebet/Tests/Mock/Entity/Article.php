<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Tools\Reflector;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\DataModel\Entity;
use Rebet\DateTime\DateTime;

class Article extends Entity
{
    public $article_id;
    public $user_id;
    public $subject;
    public $body;
    /**
     * @PhpType(DateTime::class)
     */
    public $created_at;
    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;

    public function user(bool $for_update = false, bool $eager_load = true) : ?User
    {
        return parent::belongsTo(User::class, [], $for_update, $eager_load);
    }

    /**
     * Method for unit test
     */
    public function belongsTo(string $class, array $alias = [], bool $for_update = false, bool $eager_load = true, ?string $name = null)
    {
        return parent::belongsTo($class, $alias, $for_update, $eager_load, $name ?? Reflector::caller());
    }
}

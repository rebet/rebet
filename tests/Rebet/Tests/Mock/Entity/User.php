<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\DataModel\Entity;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;

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

    protected static function relations() : array
    {
        return [
            'bank'     => ['has_one', Bank::class],
            'articles' => ['has_many', Article::class],
        ];
    }
}

<?php
namespace Rebet\Tests\Mock;

use Rebet\Common\Annotation\Nest;
use Rebet\Database\Annotation\Type;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\DataModel\Entity;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;

class User extends Entity
{
    public $user_id;
    public $name;
    /**
     * @Type(Gender::class)
     */
    public $gender;
    public $birthday;
    public $email;
    public $role;
    public $created_at;
    public $updated_at;

    /**
     * @Nest(Bank::class)
     * @Unmap
     */
    public $bank = null;

    /**
     * @Nest(Address::class)
     * @Unmap
     */
    public $shipping_addresses = [];

    public function age() : ?int
    {
        return $this->birthday ? DateTime::valueOf($this->birthday)->age() : null ;
    }
}

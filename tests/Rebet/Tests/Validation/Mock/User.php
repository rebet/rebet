<?php
namespace Rebet\Tests\Validation\Mock;

use Rebet\Validation\Validatable;
use Rebet\Validation\Annotation\Nest;

class User
{
    use Validatable;

    public $name = null;
    public $birthday = null;

    /**
     * @Nest(Bank::class)
     */
    public $bank = null;

    /**
     * @Nest(Address::class)
     */
    public $shipping_addresses = [];
}

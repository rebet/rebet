<?php
namespace Rebet\Tests\Common\Mock;

use Rebet\Common\Annotation\Nest;
use Rebet\Common\Popuratable;

class User
{
    use Popuratable;

    public $user_id;
    public $name;
    public $birthday;
    
    /**
     * @Nest(Bank::class)
     */
    public $bank = null;
    
    /**
     * @Nest(Address::class)
     */
    public $shipping_addresses = [];
}

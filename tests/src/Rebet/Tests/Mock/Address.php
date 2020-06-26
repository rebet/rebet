<?php
namespace Rebet\Tests\Mock;

use Rebet\Common\Populatable;

class Address
{
    use Populatable;

    public $user_id;
    public $zip;
    public $prefecture;
    public $address;
}

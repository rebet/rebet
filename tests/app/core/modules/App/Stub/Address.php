<?php
namespace App\Stub;

use Rebet\Tools\Reflection\Populatable;

class Address
{
    use Populatable;

    public $user_id;
    public $zip;
    public $prefecture;
    public $address;
}

<?php

declare(strict_types=1);

namespace TestApp\Stub;

use Rebet\Tools\Reflection\Populatable;

#[\AllowDynamicProperties]
class Address
{
    use Populatable;

    public $user_id;
    public $zip;
    public $prefecture;
    public $address;
}

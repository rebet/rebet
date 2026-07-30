<?php
namespace App\Stub;

use Rebet\Tools\Reflection\Describable;
use Rebet\Tools\Reflection\Populatable;

#[\AllowDynamicProperties]
class Customer
{
    use Populatable, Describable;

    public $name;
    public $birthday;
}

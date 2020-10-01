<?php
namespace Rebet\Tests\Mock;

use Rebet\Tools\Reflection\Describable;
use Rebet\Tools\Reflection\Populatable;

class Customer
{
    use Populatable, Describable;

    public $name;
    public $birthday;
}

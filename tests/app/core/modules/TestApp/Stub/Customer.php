<?php

namespace TestApp\Stub;

use Rebet\Tools\Reflection\Describable;
use Rebet\Tools\Reflection\Populatable;

#[\AllowDynamicProperties]
class Customer
{
    use Populatable;
    use Describable;

    public $name;
    public $birthday;
}

<?php

namespace TestApp\Enum;

use Rebet\Tools\Enum\Enum;

class Gender extends Enum
{
    public const MALE   = [1, 'Male'];
    public const FEMALE = [2, 'Female'];
}

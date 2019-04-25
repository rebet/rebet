<?php
namespace Rebet\Tests\Mock\Enum;

use Rebet\Enum\Enum;

class Gender extends Enum
{
    const MALE   = [1, 'Male'];
    const FEMALE = [2, 'Female'];
}

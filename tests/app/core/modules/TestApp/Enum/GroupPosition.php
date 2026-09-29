<?php

namespace TestApp\Enum;

use Rebet\Tools\Enum\Enum;

class GroupPosition extends Enum
{
    public const MANAGER = [1, 'Manager'];
    public const LEADER  = [2, 'Leader'];
    public const MEMBER  = [3, 'Member'];
}

<?php
namespace App\Enum;

use Rebet\Tools\Enum\Enum;

class GroupPosition extends Enum
{
    const MANAGER = [1, 'Manager'];
    const LEADER  = [2, 'Leader'];
    const MEMBER  = [3, 'Member'];
}

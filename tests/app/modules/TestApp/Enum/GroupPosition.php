<?php

declare(strict_types=1);

namespace TestApp\Enum;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Enum\Meta;

/**
 * @implements Enumeration<GroupPosition, int>
 */
enum GroupPosition: int implements Enumeration
{
    /** @use Enumerable<GroupPosition, int> */
    use Enumerable;

    #[Meta(label: "Manager")]
    case Manager = 1;

    #[Meta(label: "Leader")]
    case Leader = 2;

    #[Meta(label: "Member")]
    case Member = 3;
}

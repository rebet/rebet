<?php

declare(strict_types=1);

namespace TestApp\Enum;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Enum\Meta;
use Rebet\Tools\Enum\Translatable;

/**
 * @implements Enumeration<Gender, int>
 */
#[Translatable(['label'])]
enum Gender: int implements Enumeration
{
    /** @use Enumerable<Gender, int> */
    use Enumerable;

    #[Meta(label: "Male")]
    case Male = 1;

    #[Meta(label: "Female")]
    case Female = 2;
}

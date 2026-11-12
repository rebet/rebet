<?php

declare(strict_types=1);

namespace Rebet\View;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;

/**
 * EOF Line Feed Enum
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @implements Enumeration<EofLineFeed, int>
 */
enum EofLineFeed: int implements Enumeration
{
    /** @use Enumerable<EofLineFeed, int> */
    use Enumerable;

    /**
     * EOF line feed processing : Keep (Do nothing)
     */
    case Keep = 1;

    /**
     * EOF line feed processing : Trim CR/LF
     */
    case Trim = 2;

    /**
     * EOF line feed processing : Trim CR/LF then append one LF
     */
    case One = 3;

    /**
     * Process EOF line feeds.
     *
     * @param  string|null $contents
     * @return string|null
     */
    public function process(string|null $contents): string|null
    {
        if ($contents === null) {
            return null;
        }
        if ($this === self::Keep) {
            return $contents;
        }
        $contents = rtrim($contents, "\r\n");
        return $this === self::Trim ? $contents : "{$contents}\n" ;
    }
}

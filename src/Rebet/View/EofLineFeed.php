<?php
namespace Rebet\View;

use Rebet\Enum\Enum;

/**
 * EOF Line Feed Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EofLineFeed extends Enum
{
    /**
     * EOF line feed processing : Keep (Do nothing)
     */
    const KEEP = [1, 'keep'];

    /**
     * EOF line feed processing : Trim CR/LF
     */
    const TRIM = [2, 'trim'];

    /**
     * EOF line feed processing : Trim CR/LF then append one LF
     */
    const ONE  = [3, 'one'];

    /**
     * Process EOF line feeds.
     *
     * @param string|null $contents
     * @return string|null
     */
    public function process(?string $contents) : ?string
    {
        if ($contents === null) {
            return null;
        }
        if ($this->equals(static::KEEP())) {
            return $contents;
        }
        $contents = rtrim($contents, "\r\n");
        return $this->equals(static::TRIM()) ? $contents : "{$contents}\n" ;
    }
}

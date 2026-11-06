<?php

declare(strict_types=1);

namespace Rebet\Tools\Utility;

/**
 * Array Keyable Interface
 *
 * The object that implements this interface can be used as an array key via Arrays::asKey() and Arrays::tryAsKey().
 * Objects that are equal in terms of the application must return the same key.
 *
 * @see Arrays::asKey()
 * @see Arrays::tryAsKey()
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface ArrayKeyable
{
    /**
     * Get the value that is used as an array key.
     *
     * @return int|string
     */
    public function asArrayKey(): int|string;
}

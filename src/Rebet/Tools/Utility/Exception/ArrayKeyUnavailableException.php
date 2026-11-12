<?php

declare(strict_types=1);

namespace Rebet\Tools\Utility\Exception;

use Rebet\Tools\Exception\LogicException;

/**
 * Array Key Unavailable Exception
 *
 * Thrown by ArrayKeyable::asArrayKey() when the object can not be identified as an array key in its current state.
 * (e.g. the data model whose primary value is not set yet)
 * Arrays::tryAsKey() converts this exception to null, and Arrays::asKey() throws it as it is.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArrayKeyUnavailableException extends LogicException
{
    public function __construct(string $message, \Throwable|null $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

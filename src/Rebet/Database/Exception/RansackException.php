<?php
namespace Rebet\Database\Exception;

use Rebet\Common\Exception\RuntimeException;

/**
 * Ransack Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RansackException extends RuntimeException
{
    /**
     * Create a database exception.
     *
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

<?php
namespace Rebet\Log\Driver;

use Psr\Log\AbstractLogger as PsrAbstractLogger;

/**
 * Null Driver Class
 *
 * Usage: (Parameter of Constractor)
 *    'driver' [*] NullDriver::class,
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class NullDriver extends PsrAbstractLogger
{
    /**
     * Do nothing.
     *
     * @param string $level
     * @param string|\Stringable $message
     * @param array<string, mixed> $context (default: [])
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []) : void
    {
        // Do nothing.
    }
}

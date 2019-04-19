<?php
namespace Rebet\Log\Driver;

use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Rebet\Log\Log;

/**
 * Stack Driver Class
 *
 * Usage: (Parameter of Constractor)
 *    'driver'   [*] StackDriver::class,
 *    'channels' [*] array of channels,
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StackDriver extends PsrAbstractLogger
{
    /**
     * @var StackDriver[] list of called for infinite loop prevention
     */
    protected static $call_stack = [];

    /**
     * Stacked channels
     *
     * @var string[]
     */
    protected $channels = [];

    /**
     * Create Stack Driver using given channels.
     *
     * @param array $channels
     */
    public function __construct(array $channels)
    {
        $this->channels = $channels;
    }

    /**
     * Output logs to stacked channels.
     *
     * @param string $level
     * @param string|object $message
     * @param array $context (default: [])
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        static::$call_stack[] = $this;
        foreach ($this->channels as $channel) {
            $logger = Log::channel($channel);
            if ($logger === null || in_array($driver = $logger->driver(), static::$call_stack)) {
                continue;
            }
            $driver->log($level, $message, $context);
        }
        array_pop(static::$call_stack);
    }
}

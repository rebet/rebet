<?php
namespace Rebet\Log\Driver;

use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Psr\Log\LoggerInterface as PsrLogger;
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
class StackDriver extends PsrAbstractLogger implements NameableDriver
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
     * Stacked channel drivers
     *
     * @var PsrLogger[]
     */
    protected $drivers = [];

    /**
     * Name of log channel
     *
     * @var string|null
     */
    protected $name = null;

    /**
     * Create Stack Driver using given channels.
     *
     * @param array $channels
     */
    public function __construct(array $channels)
    {
        $this->channels = $channels;
        foreach ($this->channels as $channel) {
            $logger          = Log::channel($channel);
            $driver          = $logger->driver();
            $this->drivers[] = $driver instanceof NameableDriver ? $driver->withName($this->name ?? $driver->getName()) : $driver ;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setName(string $name) : self
    {
        $this->name = $name;
        foreach ($this->drivers as $driver) {
            if ($driver instanceof NameableDriver) {
                $driver->setName($name);
            }
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function withName(string $name) : self
    {
        $new          = clone $this;
        $new->name    = $name;
        $new->drivers = [];
        foreach ($this->drivers as $driver) {
            $new->drivers[] = $driver instanceof NameableDriver ? $driver->withName($name) : $driver ;
        }
        return $new;
    }

    /**
     * Output logs to stacked channels.
     *
     * @param string $level
     * @param string|\Stringable $message
     * @param array $context (default: [])
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []) : void
    {
        static::$call_stack[] = $this;
        foreach ($this->drivers as $driver) {
            if (in_array($driver, static::$call_stack)) {
                continue;
            }
            $driver->log($level, $message, $context);
        }
        array_pop(static::$call_stack);
    }
}

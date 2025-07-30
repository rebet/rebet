<?php
namespace Rebet\Log;

use Psr\Log\LoggerInterface as PsrLogger;
use Rebet\Log\Driver\NameableDriver;

/**
 * Logger Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Logger
{
    /**
     * Log Driver
     *
     * @var PsrLogger
     */
    protected $driver = null;

    /**
     * Create Logger using given log driver.
     *
     * @param PsrLogger $driver
     */
    public function __construct(PsrLogger $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Get the log driver.
     *
     * @return PsrLogger
     */
    public function driver() : PsrLogger
    {
        return $this->driver;
    }

    /**
     * Get/Set the logger name if the driver of logger implemented NameableDriver interface.
     * NOTE: If the driver not implemented NameableDriver then this method return null when get and do nothing when set.
     *
     * @param string|null $name (default: null for get name)
     * @return null|string|self
     */
    public function name(?string $name = null) 
    {
        if ($name == null) {
            return $this->driver instanceof NameableDriver ? $this->driver->getName() : null ;
        }

        if ($this->driver instanceof NameableDriver) {
            $this->driver->setName($name);
        }

        return $this;
    }

    /**
     * Output EMERGENCY level log.
     * System is unusable.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function emergency($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context, $exception);
    }

    /**
     * Output ALERT level log.
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function alert($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::ALERT, $message, $context, $exception);
    }

    /**
     * Output CRITICAL level log.
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function critical($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::CRITICAL, $message, $context, $exception);
    }

    /**
     * Output ERROR level log.
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function error($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::ERROR, $message, $context, $exception);
    }

    /**
     * Output WARNING level log.
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function warning($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::WARNING, $message, $context, $exception);
    }

    /**
     * Output NOTICE level log.
     * Normal but significant events.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function notice($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::NOTICE, $message, $context, $exception);
    }

    /**
     * Output INFO level log.
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function info($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::INFO, $message, $context, $exception);
    }

    /**
     * Output DEBUG level log.
     * Detailed debug information.
     *
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function debug($message, array $context = [], $exception = null) : void
    {
        $this->log(LogLevel::DEBUG, $message, $context, $exception);
    }

    /**
     * Output a given level log.
     *
     * @param string $level
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public function log(string $level, $message, array $context = [], $exception = null) : void
    {
        if ($exception) {
            $context['exception'] = $exception;
        }
        $this->driver->log($level, $message, $context);
    }

    /**
     * Output memory usage.
     *
     * @param string $message (default: '')
     * @param int $decimals (default: 2)
     * @param string $level (default: LogLevel::DEBUG)
     * @return void
     */
    public function memory(string $message = '', int $decimals = 2, string $level = LogLevel::DEBUG) : void
    {
        $current = number_format(memory_get_usage() / 1048576, $decimals);
        $peak    = number_format(memory_get_peak_usage() / 1048576, $decimals);
        $message = empty($message) ? "" : "{$message} : " ;
        $message = $message."Memory {$current} MB / Peak Memory {$peak} MB";
        $this->log($level, $message);
    }
}

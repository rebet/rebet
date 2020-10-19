<?php
namespace Rebet\Log;

use Exception;
use Monolog\Handler\StreamHandler;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Log\Driver\Monolog\MonologDriver;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Driver\NullDriver;
use Rebet\Log\Driver\StackDriver;
use Rebet\Tools\Config\Configurable;

/**
 * Log Class
 *
 * Output a log by specifying any driver for each channel definition.
 * The driver MUST be implements PSR-3 LoggerInterface.
 * Rebet uses Seldaek/monolog as the default log driver.
 *
 * The driver used for Rebet logging can be specified by the following definition.
 *
 *     Log::class => [
 *         'channels' => [
 *              'channel_name' => [
 *                  'driver'     => Driver::class, // PSR-3 LoggerInterface implementation class
 *                  'arg_name_1' => value_1,       // Constructor argument name and value for 'driver' class.
 *                  (snip)                         // If the argument has default value (or variadic), then the parameter can be optional.
 *                  'arg_name_n' => value_n,       // Also, you don't have to worry about the order of parameter definition.
 *              ],
 *         ]
 *     ]
 *
 * If it is difficult to build a driver with simple constructor parameter specification, you can build a driver by specifying a factory method.
 *
 *     Log::class => [
 *         'channels' => [
 *              'channel_name' => [
 *                  'driver' => function() { ... Build any log driver here ... } , // Return PSR-3 LoggerInterface implementation class
 *              ],
 *         ]
 *     ]
 *
 * Based on this specification, Rebet provides several Monolog extension driver classes that simplify driver construction.
 * The drivers prepared in the package are as follows.
 * Note: These handlers and middleware will be added sequentially.
 *
 * Drivers
 * --------------------
 * @see \Rebet\Log\Driver\NullDriver::class
 * @see \Rebet\Log\Driver\StackDriver::class
 * @see \Rebet\Log\Driver\Monolog\MonologDriver::class
 * @see \Rebet\Log\Driver\Monolog\StderrDriver::class (Liblary Default)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Log
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'unittest' => false,
            'channels' => [
                'stderr' => [
                    'driver' => StderrDriver::class,
                    'name'   => 'stderr',
                    'level'  => LogLevel::DEBUG,
                ],
                'test' => [
                    'driver' => TestDriver::class,
                    'name'   => 'test',
                    'level'  => LogLevel::DEBUG,
                ],
            ],
            'default_channel'  => 'stderr',
            'unittest_channel' => 'test',
            'fallback_log'     => defined('STDERR') ? STDERR : 'php://stderr',
        ];
    }

    /**
     * Log channels
     *
     * @var Logger[]
     */
    protected static $channels = [];

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Clear the all channels.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$channels = [];
    }

    /**
     * Get/Set unittest mode or not.
     *
     * @param bool|null $is_unittest (default: null for get unittest mode or not)
     * @return bool
     */
    public static function unittest(?bool $is_unittest = null) : bool
    {
        if ($is_unittest === null) {
            return static::config('unittest');
        }
        static::setConfig(['unittest' => $is_unittest]);
        return $is_unittest;
    }

    /**
     * Select the channel according to the configuration.
     *
     * @param string|null $channel name that configured in 'Log.channels'. (default: null for depend on configuration 'default_channel')
     * @return string
     */
    protected static function adoptChannel(?string $channel = null) : string
    {
        switch (true) {
            case static::unittest(): return static::config('unittest_channel');
        }
        return $channel ?? static::config('default_channel', false, 'default');
    }

    /**
     * Get the logger for given channel.
     *
     * @param string $channel when the null given return the default channel logger (default: null)
     * @return Logger
     */
    public static function channel(?string $channel = null) : Logger
    {
        $channel = static::adoptChannel($channel);
        if ($logger = static::$channels[$channel] ?? null) {
            return $logger;
        }

        try {
            return static::$channels[$channel] = new Logger(static::configInstantiate("channels.{$channel}", 'driver'));
        } catch (\Exception $e) {
            static::fallbackLogger()->warning("Unable to create '{$channel}' channel logger.", [], $e);
            return new Logger(new NullDriver());
        }
    }

    /**
     * Get the stacked logger using given channels.
     *
     * @param string ...$channels
     * @return Logger
     */
    public static function stack(string ...$channels) : Logger
    {
        return new Logger(new StackDriver($channels));
    }

    /**
     * Get the fallback logger.
     *
     * @return Logger
     */
    protected static function fallbackLogger() : Logger
    {
        $handler = new StreamHandler(static::config('fallback_log', false, 'php://stderr'));
        $handler->setFormatter(new TextFormatter());
        return new Logger(new MonologDriver('rebet', LogLevel::DEBUG, [$handler]));
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
    public static function emergency($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::EMERGENCY, $message, $context, $exception);
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
    public static function alert($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::ALERT, $message, $context, $exception);
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
    public static function critical($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::CRITICAL, $message, $context, $exception);
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
    public static function error($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::ERROR, $message, $context, $exception);
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
    public static function warning($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::WARNING, $message, $context, $exception);
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
    public static function notice($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::NOTICE, $message, $context, $exception);
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
    public static function info($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::INFO, $message, $context, $exception);
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
    public static function debug($message, array $context = [], $exception = null) : void
    {
        static::log(LogLevel::DEBUG, $message, $context, $exception);
    }

    /**
     * Output memory usage.
     *
     * @param string $message (default: '')
     * @param int $decimals (default: 2)
     * @return void
     */
    public static function memory(string $message = '', int $decimals = 2) : void
    {
        $current = number_format(memory_get_usage() / 1048576, $decimals);
        $peak    = number_format(memory_get_peak_usage() / 1048576, $decimals);
        $message = empty($message) ? "" : "{$message} : " ;
        $message = $message."Memory {$current} MB / Peak Memory {$peak} MB";
        static::log(LogLevel::DEBUG, $message);
    }

    /**
     * Output a log.
     *
     * @param string $level
     * @param mixed $message
     * @param array $context (default: [])
     * @param \Throwable $exception (default: null)
     * @return void
     */
    public static function log(string $level, $message, array $context = [], $exception = null) : void
    {
        static::channel()->log($level, $message, $context, $exception);
    }
}

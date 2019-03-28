<?php
namespace Rebet\Log;

use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Pipeline\Pipeline;

/**
 * Log Class
 *
 * Log output by combining arbitrary handler and middleware.
 *
 * The handlers & middleware prepared in the package are as follows.
 * Note: These handlers and middleware will be added sequentially.
 *
 * Handlers
 * --------------------
 * @see \Rebet\Log\Handler\StderrHandler::class (Liblary Default)
 * @see \Rebet\Log\Handler\FileHandler::class
 *
 * Middlewares (Liblary Default: Not use)
 * --------------------
 * @see \Rebet\Log\Middleware\WebDisplay::class
 *
 * @todo Add various handlers & middleware
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
            'log_handler'     => \Rebet\Log\Handler\StderrHandler::class,
            'log_middlewares' => [],
        ];
    }

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Log middleware pipeline
     *
     * @var Rebet\Pipeline\Pipeline
     */
    private static $pipeline = null;

    /**
     * Output TRACE level log.
     *
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @return void
     */
    public static function trace($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::TRACE(), $message, $var, $error);
    }

    /**
     * Output DEBUG level log.
     *
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @return void
     */
    public static function debug($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::DEBUG(), $message, $var, $error);
    }

    /**
     * Output INFO level log.
     *
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @return void
     */
    public static function info($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::INFO(), $message, $var, $error);
    }

    /**
     * Output WARN level log.
     *
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @return void
     */
    public static function warn($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::WARN(), $message, $var, $error);
    }

    /**
     * Output ERROR level log.
     *
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @return void
     */
    public static function error($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::ERROR(), $message, $var, $error);
    }

    /**
     * Output FATAL level log.
     *
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @return void
     */
    public static function fatal($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::FATAL(), $message, $var, $error);
    }

    /**
     * Output memory usage.
     *
     * @todo Should it be middleware?
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
        self::log(LogLevel::INFO(), $message);
    }

    /**
     * Initialize the logger.
     *
     * @param callable|null $handler function(LogContext $log):string|array|null or any handler class uses LogHandleable trait.
     * @param array|null $middlewares
     * @return void
     */
    public static function init(?callable $handler = null, ?array $middlewares = null) : void
    {
        self::terminate();
        self::$pipeline = (new Pipeline())
            ->through($middlewares ?? self::config('log_middlewares', false, []))
            ->then($handler ?? self::configInstantiate('log_handler'))
            ;
    }

    /**
     * Terminate the logger
     *
     * @return void
     */
    public static function terminate() : void
    {
        if (self::$pipeline !== null) {
            Log::$pipeline->invoke('terminate');
            Log::$pipeline->getDestination()->terminate();
        }
    }

    /**
     * Output a log.
     *
     * @param LogLevel $level
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @return void
     */
    private static function log(LogLevel $level, $message, array $var = [], $error = null) : void
    {
        if (self::$pipeline === null) {
            self::init();
        }
        self::$pipeline->send(new LogContext(DateTime::now(), $level, $message, $var, $error));
    }

    /**
     * Error handle for error handler
     *
     * @param array $error array of error_get_last()
     * @return void
     */
    public static function errorHandle(array $error) : void
    {
        self::log(LogLevel::errorTypeOf($error['type']), "{$error['message']} ({$error['file']}:{$error['line']})", [], $error);
    }
}

// Error handler registration
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    Log::errorHandle(['type' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline]);
});

// Shutdown handler registration
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        Log::errorHandle($error);
    }
    Log::terminate();
});

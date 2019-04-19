<?php
namespace Rebet\Log;

use Psr\Log\LogLevel as PsrLogLevel;

/**
 * Log Level Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LogLevel extends PsrLogLevel
{
    /**
     * @var string[]
     */
    protected const ERROR_TYPE_LABELS = [
        E_CORE_ERROR        => 'E_CORE_ERROR',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_PARSE             => 'E_PARSE',
        E_ERROR             => 'E_ERROR',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_WARNING           => 'E_WARNING',
        E_USER_WARNING      => 'E_USER_WARNING',
        E_CORE_WARNING      => 'E_CORE_WARNING',
        E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        E_NOTICE            => 'E_NOTICE',
        E_USER_NOTICE       => 'E_USER_NOTICE',
        E_DEPRECATED        => 'E_DEPRECATED',
        E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
        E_STRICT            => 'E_STRICT',
    ];

    public static function errorTypeLabel(int $type) : string
    {
        return static::ERROR_TYPE_LABELS[$type] ?? "E_UNKNOWN({$type})" ;
    }

    /**
     * Convert error type of E_* format to PSR-3 LogLevel.
     *
     * @param int $type
     * @return string
     */
    public static function errorTypeOf(int $type) : string
    {
        switch ($type) {
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                return self::CRITICAL;
            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return self::ERROR;
            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return self::WARNING;
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_STRICT:
                return self::NOTICE;
        }
        return self::WARNING;
    }
}

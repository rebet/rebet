<?php

declare(strict_types=1);

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

    public static function errorTypeLabel(int $type): string
    {
        return static::ERROR_TYPE_LABELS[$type] ?? "E_UNKNOWN({$type})" ;
    }

    /**
     * Convert error type of E_* format to PSR-3 LogLevel.
     *
     * @param  int    $type
     * @return string
     */
    public static function errorTypeOf(int $type): string
    {
        return match ($type) {
            E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE                             => self::CRITICAL,
            E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR                         => self::ERROR,
            E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING       => self::WARNING,
            E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT => self::NOTICE,
            default                                                            => self::WARNING,
        };
    }
}

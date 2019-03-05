<?php
namespace Rebet\Log;

use Rebet\Enum\Enum;

/**
 * Log Level Enum Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LogLevel extends Enum
{
    const FATAL = [0, 'FATAL'];
    const ERROR = [1, 'ERROR'];
    const WARN  = [2, 'WARN '];
    const INFO  = [3, 'INFO '];
    const DEBUG = [4, 'DEBUG'];
    const TRACE = [5, 'TRACE'];

    /**
     * Convert error type of E_* format to LogLevel.
     *
     * @param int $type
     * @return LogLevel
     */
    public static function errorTypeOf(int $type) : LogLevel
    {
        switch ($type) {
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                return self::FATAL();
            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return self::ERROR();
            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return self::WARN();
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_STRICT:
                return self::TRACE();
        }
        return self::WARN();
    }

    /**
     * This enum do not need to translate.
     *
     * @return boolean
     */
    protected function translatable() : bool
    {
        return false;
    }

    /**
     * It checks whether it is above the given log level.
     *
     * @param LogLevel $level
     */
    public function higherEqual(LogLevel $level) : bool
    {
        return $this->value <= $level->value;
    }

    /**
     * It checks whether it is less than the given log level.
     *
     * @param LogLevel $level
     */
    public function lowerThan(LogLevel $level) : bool
    {
        return $this->value > $level->value;
    }
}

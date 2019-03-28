<?php
namespace Rebet\Log;

use Rebet\DateTime\DateTIme;

/**
 * Log Context Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LogContext
{
    /**
     * @var DateTime
     */
    public $now;

    /**
     * @var LogLevel
     */
    public $level;

    /**
     * @var string|array
     */
    public $message;

    /**
     * @var array
     */
    public $var;

    /**
     * @var array|\Throwable|null
     */
    public $error;

    /**
     * @var array
     */
    public $extra;

    /**
     * Create a log context.
     *
     * @param DateTime $now
     * @param LogLevel $level
     * @param mixed $message
     * @param array $var (default: [])
     * @param \Throwable|array $error exception or array of error_get_last() (default: null)
     * @param array $extra infomation (default: [])
     */
    public function __construct(DateTime $now, LogLevel $level, $message, array $var = [], $error = null, array $extra = [])
    {
        $this->now     = $now;
        $this->level   = $level;
        $this->message = $message;
        $this->var     = $var;
        $this->error   = $error;
        $this->extra   = $extra;
    }
}

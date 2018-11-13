<?php
namespace Rebet\Log\Handler;

use Rebet\Config\Configurable;
use Rebet\Log\Formatter\LogFormatter;
use Rebet\Log\LogContext;

/**
 * Abstract Formattable Handler Class
 *
 * You need to define the following config setting with defaultConfig() in sub class.
 * - log_formatter
 * - log_level
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class FormattableHandler
{
    use Configurable, LogHandleable;

    /**
     * Log Formatter
     *
     * @var Rebet\Log\Formatter\LogFormatter
     */
    protected $formatter = null;

    /**
     * Create a log handler using given fomatter.
     *
     * @param LogFormatter|null $formatter
     */
    public function __construct(?LogFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? self::configInstantiate('log_formatter') ;
    }

    /**
     * handle log context.
     *
     * @param LogContext $log
     * @return string|array|null Formatted log data or null (when not logging)
     */
    public function handle(LogContext $log)
    {
        if ($log->level->lowerThan(self::config('log_level'))) {
            return null;
        }
        $formatted_log = $this->formatter->format($log);
        $this->report($log, $formatted_log);
        return $formatted_log;
    }

    /**
     * Report formatted log data.
     *
     * @param LogContext $log
     * @param string|array $formatted_log
     */
    abstract protected function report(LogContext $log, $formatted_log) : void ;
}

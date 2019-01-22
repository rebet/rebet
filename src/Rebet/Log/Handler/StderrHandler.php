<?php
namespace Rebet\Log\Handler;

use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;

/**
 * Stderr Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StderrHandler extends FormattableHandler
{
    public static function defaultConfig()
    {
        return [
            'log_level'     => LogLevel::ERROR(),
            'log_formatter' => \Rebet\Log\Formatter\DefaultFormatter::class,
        ];
    }

    /**
     * Create a log handler
     */
    public function __construct(?LogFormatter $formatter = null)
    {
        parent::__construct($formatter);
    }

    /**
     * Report formatted log data.
     *
     * @param LogContext $log
     * @param string|array $formatted_log
     */
    protected function report(LogContext $log, $formatted_log) : void
    {
        if (\is_array($formatted_log)) {
            $formatted_log = \print_r($formatted_log, true);
        }
        fwrite(STDERR, "{$formatted_log}\n");
    }

    /**
     * {@inheritDoc}
     */
    public function terminate() : void
    {
        // Nothing to do
    }
}

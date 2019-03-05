<?php
namespace Rebet\Log\Formatter;

use Rebet\Common\Strings;
use Rebet\Log\Log;
use Rebet\Log\LogContext;

/**
 * Default Formatter Class
 *
 * @todo implement log format
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DefaultFormatter implements LogFormatter
{
    /**
      * Format log by given log context.
      *
      * @param LogContext $log context
      * @return string|array formatted log
      */
    public function format(LogContext $log)
    {
        $body    = '';
        $message = $log->message;

        if (!is_string($message) && !method_exists($message, '__toString')) {
            $message = Strings::rtrim(print_r($message, true), "\n");
        }
        $prefix = $log->now->format('Y-m-d H:i:s.u')." ".getmypid()." [{$log->level}] ";
        $body   = $prefix.$message; // Strings::indent($message, $prefix);

        if ($log->var) {
            $body .= Strings::indent(
                "\n*** VAR ***".
                "\n".Strings::rtrim(print_r($log->var, true), "\n"),
                "== " //"{$prefix}== "
            );
        }

        if ($log->extra) {
            $body .= Strings::indent(
                "\n*** EXTRA ***".
                "\n".Strings::rtrim(print_r($log->extra, true), "\n"),
                "-- " //"{$prefix}-- "
            );
        }

        if ($log->error) {
            $body .= Strings::indent(
                "\n*** EXCEPTION ***".
                ($log->error instanceof \Throwable ? "\n{$log->error}" : "\n".Strings::rtrim(print_r($log->error, true), "\n")),
                "** " //"{$prefix}** "
            );
        }

        return $body;
    }
}

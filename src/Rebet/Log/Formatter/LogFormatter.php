<?php
namespace Rebet\Log\Formatter;

use Rebet\Log\LogContext;

/**
 * Log Formatter Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface LogFormatter
{
    /**
     * Format log using given log context.
     *
     * @param LogContext $log context
     * @return string|array formatted log info
     */
    public function format(LogContext $log) ;
}

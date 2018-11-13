<?php
namespace Rebet\Log\Handler;

use Rebet\Log\LogContext;

/**
 * Log Handleable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait LogHandleable
{
    /**
     * Handle the log.
     *
     * @param LogContext $log
     * @return string|array|null Formatted log data or null (when not logging)
     */
    abstract public function handle(LogContext $log) ;

    /**
     * Terminate the log handler
     *
     * @return void
     */
    abstract public function terminate() : void ;

    /**
     * Allow log handlers to be processed by Pipeline.
     *
     * @param LogContext $log
     * @return string|array|null Formatted log data or null (when not logging)
     */
    public function __invoke($log)
    {
        return $this->handle($log);
    }
}

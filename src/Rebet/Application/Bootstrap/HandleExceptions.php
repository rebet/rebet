<?php
namespace Rebet\Application\Bootstrap;

use ErrorException;
use Rebet\Application\Kernel;
use Rebet\Common\System;

/**
 * Handle Exceptions Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class HandleExceptions implements Bootstrapper
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        ini_set('display_errors', 'Off');
        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        $fallbacker = function (\Throwable $e) use ($kernel) {
            $status = $kernel->fallback($e);
            $kernel->terminate();
            System::exit($status);
        };
        set_exception_handler($fallbacker);

        register_shutdown_function(function () use ($kernel, $fallbacker) {
            if ($error = error_get_last()) {
                $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
                if (in_array($error['type'], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE])) {
                    $fallbacker($exception);
                } else {
                    $kernel->report($exception);
                }
            }
        });
    }
}

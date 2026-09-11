<?php
declare(strict_types=1);

namespace App\Console;

use Override;
use Rebet\Application\Console\CliExceptionHandler;
use Symfony\Component\Console\Input\InputInterface;

/**
 * CLI Exception Handler Class For {! $code_name !} Application
 *
 * NOTE: If we want to change CLI exception handling, we can do it by override methods of this class.
 */
class AppCliExceptionHandler extends CliExceptionHandler
{
    /**
     * Report an exception.
     * Just only report, this function do not display result.
     *
     * @param InputInterface $input
     * @param int|null $result
     * @param \Throwable $e
     * @return void
     */
    #[Override]
    public function report($input, $result, \Throwable $e) : void
    {
        parent::report($input, $result, $e);
    }

    /**
     * Handle an exception
     *
     * @param InputInterface|null $input
     * @param \Throwable $e
     * @return int
     */
    #[Override]
    public function handle($input, \Throwable $e)
    {
        return parent::handle($input, $e);
    }
}

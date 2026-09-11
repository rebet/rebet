<?php
declare(strict_types=1);

namespace App\Http;

use Override;
use Rebet\Application\Http\WebExceptionHandler;
use Rebet\Http\Request;
use Rebet\Http\Response;

/**
 * Web Exception Handler Class For {! $code_name !} Application
 *
 * NOTE: If we want to change WEB exception handling, we can do it by override methods of this class.
 */
class AppWebExceptionHandler extends WebExceptionHandler
{
    /**
     * Report an exception.
     * Just only report, this function do not display result.
     *
     * @param Request $input
     * @param Response|null $result
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
     * @param Request|null $input
     * @param \Throwable $e
     * @return Response
     */
    #[Override]
    public function handle($input, \Throwable $e)
    {
        return parent::handle($input, $e);
    }
}

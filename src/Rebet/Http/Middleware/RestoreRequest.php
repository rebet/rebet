<?php
namespace Rebet\Http\Middleware;

use Rebet\Http\Request;
use Rebet\Http\Response;

/**
 * Restore Request Middleware Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RestoreRequest
{
    /**
     * Handle Restore Request Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return void
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        return $next($request->restore());
    }
}

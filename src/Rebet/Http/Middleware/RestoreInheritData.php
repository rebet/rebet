<?php
namespace Rebet\Http\Middleware;

use Rebet\Http\Request;
use Rebet\Http\Response;

/**
 * [Routing Middleware] Restore Inherit Data Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RestoreInheritData
{
    /**
     * Handle Restore Inherit Data Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return void
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        return $next($request->restoreInheritData());
    }
}

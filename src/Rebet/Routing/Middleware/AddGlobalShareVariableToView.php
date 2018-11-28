<?php
namespace Rebet\Routing\Middleware;

use Rebet\Http\Request;
use Rebet\Http\Response;

/**
 * Add Global Share Variable To View Middleware Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AddGlobalShareVariableToView
{
    /**
     * Handle Add Global Share Variable To View Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        View::share([
            'request' => $request,
            'route'   => $request->route,
            'prefix'  => $request->route->prefix,
            'session' => $request->session(),
        ]);
        return $next($request);
    }
}

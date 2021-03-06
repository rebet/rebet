<?php
namespace Rebet\Middleware\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\View\View;

/**
 * [Routing Middleware] Add Global Share Variable To View Class
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
            'prefix'  => $request->getRoutePrefix(),
            'session' => $request->session(),
        ]);
        return $next($request);
    }
}

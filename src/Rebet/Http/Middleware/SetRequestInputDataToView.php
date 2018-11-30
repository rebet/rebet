<?php
namespace Rebet\Http\Middleware;

use Rebet\Http\Input;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\View\View;

/**
 * [Routing Middleware] Set Request Input Data To View Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SetRequestInputDataToView
{
    /**
     * Handle Set Request Input Data To View Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return void
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        View::share('input', new Input($request));
        return $next($request);
    }
}

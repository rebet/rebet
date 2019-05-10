<?php
namespace Rebet\Middleware\Routing;

use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Http\Response;

/**
 * [Routing Middleware] Add Queued Cookies To Response Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AddQueuedCookiesToResponse
{
    /**
     * Handle Add Queued Cookies To Response Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        $response = $next($request);
        foreach (Cookie::queued() as $cookie) {
            $response->setCookie($cookie);
        }
        return $response;
    }
}

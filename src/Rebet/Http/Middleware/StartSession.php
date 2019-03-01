<?php
namespace Rebet\Http\Middleware;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\Session\Session;

/**
 * [Routing Middleware] Start Session Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StartSession
{
    /**
     * Handle Start Session Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return void
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        $session = new Session();
        $session->start();
        $request->setRebetSession($session);

        $responce = $next($request);

        return $responce;
    }

    /**
     * Terminate the middleware.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        $request->session()->save();
    }
}

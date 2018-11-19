<?php
namespace Rebet\Auth\Middleware;

use Rebet\Auth\Auth;
use Rebet\Http\Request;

/**
 * Authenticate Middleware Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        return Auth::authenticate($request) ?? $next($request);
    }
}

<?php
namespace Rebet\Foundation\Http\Middleware;

use Rebet\Foundation\App;
use Rebet\Http\Request;

/**
 * Set Channel To Request Middleware Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SetChannelToRequest
{
    /**
     * Handle Set Channel Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return void
     */
    public function handle(Request $request, \Closure $next)
    {
        $request->channel = App::getChannel();
        return $next($request);
    }
}

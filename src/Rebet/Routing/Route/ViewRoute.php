<?php
namespace Rebet\Routing\Route;

use Rebet\Auth\Auth;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\ViewSelector;
use Rebet\View\View;

/**
 * View Route Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ViewRoute extends ClosureRoute
{
    /**
     * Create a view route
     *
     * @param string $uri
     * @param string $name
     * @param array $args (default: [])
     * @param bool $apply_change (default: true)
     */
    public function __construct(string $uri, string $name, array $args = [], bool $apply_change = true)
    {
        parent::__construct([], $uri, function (Request $request) use ($name, $args, $apply_change) {
            $selector = new ViewSelector($request, Auth::user());
            $view     = $selector->view($name, $apply_change);
            if (!$view->exists()) {
                throw new RouteNotFoundException("View route [{$name}] (possible: ".join(', ', $view->getPossibleNames()).") not found in [ ".(implode(', ', $view->getPaths()))." ]. An exception occurred while processing the view.");
            }
            return Responder::toResponse($view->with(array_merge($request->input(), $request->attributes->all(), $args)));
        });
    }
}

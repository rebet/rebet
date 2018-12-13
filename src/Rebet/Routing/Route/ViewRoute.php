<?php
namespace Rebet\Routing\Route;

use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Routing\Exception\RouteNotFoundException;
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
     * Create a redirect route
     *
     * @param string $uri
     * @param string $name
     * @param array $args (default: [])
     */
    public function __construct(string $uri, string $name, array $args = [])
    {
        $self = $this;
        parent::__construct([], $uri, function (Request $request) use ($self, $name, $args) {
            $view = View::of($name);
            if (!$view->exists()) {
                throw RouteNotFoundException::by("View route [{$name}] not found. An exception occurred while processing the view.");
            }
            return Responder::toResponse($view->with(array_merge($request->input(), $request->attributes->all(), $args)));
        });
    }
}

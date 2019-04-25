<?php
namespace Rebet\Routing\Route;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Routing\RouteAction;

/**
 * Closure Route class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ClosureRoute extends DeclarativeRoute
{
    /**
     * Closure action
     *
     * @var \Closure
     */
    protected $action = null;

    /**
     * Create a closure route
     *
     * @param array $methods
     * @param string $uri
     * @param callable $action function([Request $request,] mixed ...$route_parameters)
     */
    public function __construct(array $methods, string $uri, callable $action)
    {
        parent::__construct($methods, $uri);
        $this->action = \Closure::fromCallable($action);
    }

    /**
     * Cleate a route action for this route.
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    protected function createRouteAction(Request $request) : RouteAction
    {
        return new RouteAction($this, new \ReflectionFunction($this->action));
    }

    /**
     * Terminate the route.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response) : void
    {
        // Do Nothing.
    }
}

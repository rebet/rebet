<?php
declare(strict_types=1);

namespace Rebet\Routing\Route;

use Rebet\Attribute\AttributedMethod;
use Rebet\Auth\Attribute\Guard;
use Rebet\Auth\Attribute\Role;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\RouteAction;

/**
 * Route class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Route implements \Stringable
{
    /**
     * Regex patterns for routing parameters.
     *
     * @var array<string, string>
     */
    protected $wheres = [];

    /**
     * Route action.
     *
     * @var RouteAction|null
     */
    protected $route_action = null;

    /**
     * The prefix path of this route.
     *
     * @var string
     */
    public $prefix = '';

    /**
     * Routing target URI.
     *
     * @var string|null
     */
    public $uri = null;

    /**
     * Middlewares for this route.
     *
     * @var array<int, string>
     */
    protected $middlewares = [];

    /**
     * Guard name for this route.
     *
     * @var string
     */
    protected $guard = null;

    /**
     * The roles for this route
     *
     * @var array<int, string>
     */
    protected $roles = [];

    /**
     * Configure regex check of routing parameters.
     *
     * @param array<string, string>|string $name or [$name => $regex, ...]
     * @param string|null $regex
     * @return self
     */
    public function where($name, string|null $regex = null) : self
    {
        foreach (is_array($name) ? $name : [$name => $regex] as $key => $value) {
            $this->wheres[$key] = $value;
        }
        return $this;
    }

    /**
     * It checks whether the target request matches this route setting.
     *
     * Note that the routing parameters captured during the matching process are stored in $request->attributes.
     *
     * If false is returned as a match result, subsequent route verification is performed.
     * Throw RouteNotFoundException if subsequent route verification is not done.
     *
     * @param Request $request
     * @return bool
     * @throws RouteNotFoundException
     */
    public function match(Request $request) : bool
    {
        $vars = $this->analyze($request);
        if ($vars === null) {
            return false;
        }
        $request->attributes->add($vars);
        $request->route     = $this;
        $this->route_action = $this->createRouteAction($request);
        return true;
    }

    /**
     * It analyzes the given request and analyzes whether it matches this route.
     * Returns the routing parameters captured during the analysis process.
     *
     * If null is returned as an analysis result, subsequent route verification is performed.
     * Throw RouteNotFoundException if subsequent route verification is not done.
     *
     * @param Request $request
     * @return array<string, string>|null
     * @throws RouteNotFoundException
     */
    abstract protected function analyze(Request $request) : array|null;

    /**
     * Returns the route action for processing the request matched.
     * For subclasses, additional attribute verification etc. can be done here.
     *
     * If routing is not performed by additional verification, please throw RouteNotFoundException.
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    abstract protected function createRouteAction(Request $request) : RouteAction ;

    /**
     * Return the default view name of this route.
     *
     * @return string
     */
    abstract public function defaultView() : string ;

    /**
     * Perform routing processing.
     * This method is executed as the destination of the route middleware pipeline.
     *
     * @param Request $request
     * @return Response
     * @throws RouteNotFoundException
     */
    public function handle(Request $request) : Response
    {
        return $this->route_action->invoke($request);
    }

    /**
     * Terminate the route.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    abstract public function terminate(Request $request, Response $response) : void ;

    /**
     * Allow the Pipeline to process the route object.
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request)
    {
        return $this->handle($request);
    }

    /**
     * Get the method attribute accessor for this route.
     *
     * @return AttributedMethod|null
     */
    public function getAttributedMethod() : AttributedMethod|null
    {
        return $this->route_action ? $this->route_action->getAttributedMethod() : null ;
    }

    /**
     * Gets the attribute associated with this route.
     *
     * @param string $attribute
     * @return mixed
     */
    public function attribute(string $attribute)
    {
        return $this->route_action ? $this->route_action->attribute($attribute) : null ;
    }

    /**
     *  Get or set the middlewares attached to the route.
     *
     * @param mixed ...$middlewares
     * @return self|array<int, string>
     */
    public function middlewares(...$middlewares)
    {
        if (empty($middlewares)) {
            return $this->middlewares;
        }
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * Get or set the roles attached to the route.
     *
     * @param string|array<int, string>|null $roles
     * @return self|array<int, string>
     */
    public function roles($roles = null)
    {
        if ($roles === null) {
            $role = $this->attribute(Role::class);
            return $role ? $role->names : $this->roles ;
        }
        $this->roles = func_get_args();
        return $this;
    }

    /**
     *  Get or set the guard name attached to the route.
     *
     * @param string|null $name
     * @return self|string|null
     */
    public function guard(string|null $name = null)
    {
        if ($name === null) {
            $guard = $this->attribute(Guard::class);
            return $guard ? $guard->name : $this->guard ;
        }
        $this->guard = $name;
        return $this;
    }
}

<?php
namespace Rebet\Routing;

use Rebet\Common\Path;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\Foundation\App;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Pipeline\Pipeline;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Route\ControllerRoute;
use Rebet\Routing\Route\MethodRoute;
use Rebet\Routing\Route\RedirectRoute;
use Rebet\Routing\Route\Route;
use Rebet\Routing\Route\ViewRoute;
use Rebet\View\View;

/**
 * Router Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Router
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'middlewares'              => [],
            'default_fallback_handler' => null,
        ];
    }

    /**
     * Route search tree
     *
     * @var array
     */
    protected static $routing_tree = [];
    
    /**
     * Default Route
     *
     * @var array [prefix => Route]
     */
    protected static $default_route = [];

    /**
     * Fallback action
     *
     * @var \Closure
     */
    protected static $fallback = null;

    /**
     * Current route
     *
     * @var Route
     */
    protected static $current = null;
    
    /**
     * Current routing rule builder.
     *
     * @var Router
     */
    protected static $rules = null;

    /**
     * Route middleware pipeline
     *
     * @var Rebet\Pipeline\Pipeline
     */
    protected static $pipeline = null;

    /**
     * The primary prefix
     *
     * @var string
     */
    protected static $primary_prefix = null;
    
    /**
     * Clear all routing rules.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$routing_tree   = [];
        static::$current        = null;
        static::$rules          = null;
        static::$pipeline       = null;
        static::$default_route  = [];
        static::$fallback       = null;
        static::$primary_prefix = null;
    }

    /**
     * Register a new GET route with the router.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public static function get(string $uri, $action) : Route
    {
        return static::match(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route with the router.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public static function post(string $uri, $action) : Route
    {
        return static::match('POST', $uri, $action);
    }

    /**
     * Register a new PUT route with the router.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public static function put(string $uri, $action) : Route
    {
        return static::match('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public static function patch(string $uri, $action) : Route
    {
        return static::match('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public static function delete(string $uri, $action) : Route
    {
        return static::match('DELETE', $uri, $action);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public static function options(string $uri, $action) : Route
    {
        return static::match('OPTIONS', $uri, $action);
    }

    /**
     * Register a new route responding to all methods.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public static function any(string $uri, $action) : Route
    {
        return static::match([], $uri, $action);
    }
    
    /**
     * Register a new route responding to some methods.
     * If given methods is empty(=[]) then match all method.
     *
     * Please note that routing annotation is not interpreted by declarative routing setting by this method.
     *
     * @param array|string $methods
     * @param string $uri
     * @param string|callable $action
     * @return Route
     */
    public static function match($methods, string $uri, $action) : Route
    {
        $route   = null;
        $methods = array_map('strtoupper', (array)$methods);
        if (is_callable($action)) {
            $route = new ClosureRoute($methods, $uri, $action);
        } elseif (is_string($action)) {
            $route = new MethodRoute($methods, $uri, $action);
        } else {
            throw new \LogicException("Invalid action type for declarative routing.");
        }
        
        return static::addRoute($route);
    }
   
    /**
     * Sets the controller route that matches the specified URI.
     * Various routing annotations are available for detailed access control.
     *
     * In addition, the where condition set for this route is global setting with controller scope.
     * Please use the @Where routing annotation if you want to set the where condition on an individual action basis.
     *
     * @see Rebet\Routing\Annotation
     *
     * @param string $uri
     * @param string $controller
     * @return Route
     */
    public static function controller(string $uri, string $controller) : Route
    {
        return static::addRoute(new ControllerRoute($uri, $controller));
    }

    /**
     * Sets the redirect route that matches the specified URI.
     *
     * You can use '{key}' replacement in the given destination when use '{key}' placeholder in given uri.
     * If you do not use '{key}' replacement in the destination, then '{key}' placeholder become query string.
     *
     * @param string $uri
     * @param string $destination
     * @param array $query (default: [])
     * @param integer $status (deafult: 302)
     * @return Route
     */
    public static function redirect(string $uri, string $destination, array $query = [], int $status = 302) : Route
    {
        return static::addRoute(new RedirectRoute($uri, $destination, $query, $status));
    }

    /**
     * Sets the view route that matches the specified URI.
     *
     * You can use '{key}' placeholder in given uri for view arguments.
     *
     * @param string $uri
     * @param string $name
     * @param array $args (default: [])
     * @return Route
     */
    public static function view(string $uri, string $name, array $args = []) : Route
    {
        return static::addRoute(new ViewRoute($uri, $name, $args));
    }
    
    /**
     * Add given route to the router.
     * This method constructs an incomplete route search tree for route resolution speeding up.
     *
     * @param Route $route
     * @return Route given route
     */
    protected static function addRoute(Route $route) : Route
    {
        if (!static::$rules) {
            throw new \LogicException("Routing rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        static::applyRulesTo($route);
        static::digging(static::$routing_tree, explode('/', Strings::latrim($route->prefix.$route->uri, '{')), $route);
        return $route;
    }
    
    /**
     * Stores the root object while digging the route search tree.
     *
     * @param array $tree
     * @param array $nests
     * @param Route $route
     * @return void
     */
    private static function digging(array &$tree, array $nests, Route $route) : void
    {
        if (empty($nests)) {
            if (!isset($tree[':routes:'])) {
                $tree[':routes:'] = [];
            }
            $tree[':routes:'][] = $route;
            return;
        }
        $nest = array_shift($nests);
        if (empty($nest)) {
            static::digging($tree, $nests, $route);
            return;
        }
        if (!isset($tree[$nest])) {
            $tree[$nest] = [];
        }
        static::digging($tree[$nest], $nests, $route);
        return;
    }

    /**
     * Set the default route.
     *
     * @param mixed $route Route object or instantiatable setting that can generate route
     * @return void
     */
    public static function default($route) : Route
    {
        if (!static::$rules) {
            throw new \LogicException("Routing default rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        $route = Reflector::instantiate($route);
        static::applyRulesTo($route);
        static::$default_route[$route->prefix] = $route;
        return $route;
    }

    /**
     * Apply roules to given route.
     *
     * @param Route $route
     * @return void
     */
    protected static function applyRulesTo(Route &$route) : void
    {
        $route->prefix = static::$rules->prefix;

        $middlewares = static::$rules->middlewares;
        $route->middlewares(...$middlewares);

        $roles = static::$rules->roles;
        $route->roles(...$roles);
        
        $route->auth(static::$rules->auth);
    }

    /**
     * Handle the given request.
     *
     * @param Request $request
     * @return Response
     */
    public static function handle(Request $request) : Response
    {
        $route = null;
        try {
            $route            = static::findRoute($request);
            static::$current  = $route;
            static::$pipeline = (new Pipeline())->through(array_merge(
                static::config('middlewares.'.App::getChannel(), false, []),
                $route->middlewares()
            ))->then($route);
            return static::$pipeline->send($request);
        } catch (\Throwable $e) {
            return static::handleFallback($request, $e);
        }
    }
    
    /**
     * Handle fallback.
     *
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    protected static function handleFallback(Request $request, \Throwable $e) : Response
    {
        if (empty(static::$fallback)) {
            return static::handleDefaultFallback($request, $e);
        }

        $root_fallback = null;
        $request_uri   = $request->getRequestPath();
        foreach (static::$fallback as $prefix => $fallback) {
            if ($prefix === '') {
                $root_fallback = $fallback;
            }
            if (Strings::startsWith($request_uri, "{$prefix}/")) {
                return $fallback($request, $e);
            }
        }
        if ($root_fallback) {
            return $root_fallback($request, $e);
        }

        return static::handleDefaultFallback($request, $e);
    }

    /**
     * Handle default fallback.
     *
     * @param Request $request
     * @param Route|null $route
     * @param \Throwable $e
     * @return Response
     */
    protected static function handleDefaultFallback(Request $request, \Throwable $e) : Response
    {
        $fallback = static::config('default_fallback_handler', false);
        if ($fallback) {
            $fallback = is_callable($fallback) ? \Closure::fromCallable($fallback) : Reflector::instantiate($fallback) ;
            return $fallback($request, $e);
        }
        throw $e;
    }

    /**
     * Search route matching given request.
     *
     * @param Request $request
     * @return Route
     */
    protected static function findRoute(Request $request) : Route
    {
        $request_uri  = $request->getRequestPath();
        $paths        = explode('/', $request_uri);
        $routing_tree = static::$routing_tree;

        foreach ($paths as $path) {
            if (Utils::isBlank($path)) {
                continue;
            }
            if (!isset($routing_tree[$path])) {
                break;
            }
            $routing_tree = $routing_tree[$path];
        }

        if (isset($routing_tree[':routes:'])) {
            foreach ($routing_tree[':routes:'] as $route) {
                if ($route->match($request)) {
                    return $route;
                }
            }
        }

        if (static::$default_route) {
            $default = null;
            foreach (static::$default_route as $prefix => $route) {
                if ($prefix === '' && $route->match($request)) {
                    $default = $route;
                }
                if (Strings::startsWith($request_uri, "{$prefix}/") && $route->match($request)) {
                    return $route;
                }
            }
            if ($default) {
                return $default;
            }
        }

        throw RouteNotFoundException::by("Route {$request->getMethod()} {$request_uri} not found.");
    }
    
    /**
     * Terminate the route and middlewares.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public static function terminate(Request $request, Response $response) : void
    {
        if (static::$pipeline !== null) {
            static::$pipeline->invoke('terminate', $request, $response);
            static::$pipeline->getDestination()->terminate($request, $response);
        }
    }

    /**
     * Get the route that is currently being routed.
     *
     * @return Route|null
     */
    public static function current() : ?Route
    {
        return static::$current;
    }

    /**
     * Get or Set the primary prefix.
     *
     * @param string|null $prefix (default: null)
     * @return string|null
     */
    public static function primaryPrefix(?string $prefix = null) : ?string
    {
        if ($prefix === null) {
            return static::$primary_prefix;
        }
        static::$primary_prefix = $prefix;
    }

    // ====================================================
    // Router instance for Routing Rules Build
    // ====================================================

    /**
     * The channel for current configuring routing rules.
     *
     * @var string
     */
    protected $channel = null;

    /**
     * Skip routing rules setting.
     *
     * @var boolean
     */
    protected $skip = false;

    /**
     * The prefix path for this rules.
     *
     * @var string
     */
    public $prefix = '';

    /**
     * The middlewares for this rules.
     *
     * @var array
     */
    protected $middlewares = [];
    
    /**
     * The roles/abilities for this rules.
     *
     * @var array
     */
    protected $roles = [];
    
    /**
     * The authenticator name for this rules
     *
     * @var string|null
     */
    protected $auth = null;

    /**
     * Create a routing rules builder for Router.
     */
    protected function __construct(string $channel)
    {
        $this->channel = $channel;
        $this->skip    = $channel !== App::getChannel();
    }

    /**
     * Set new routing rules for given channel.
     *
     * @param string $channel
     * @return void
     */
    public static function rules(string $channel) : self
    {
        return new static($channel);
    }
    
    /**
     * Set the prefix path for this rules.
     * If the primary prefix is not set then set the given prefix to primary prefix.
     *
     * @param string $prefix
     * @return self
     */
    public function prefix(string $prefix) : self
    {
        $this->prefix = Path::normalize($prefix);
        if (static::$primary_prefix === null) {
            static::$primary_prefix = $this->prefix;
        }
        return $this;
    }

    /**
     * Set the middlewares for this rules.
     *
     * @param string ...$middlewares
     * @return self
     */
    public function middlewares(...$middlewares) : self
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * Set the roles/abilities for this rules.
     *
     * @param string|array ...$roles
     * @return self
     */
    public function roles(...$roles) : self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Set the authenticator name for this rules.
     *
     * @param string $auth
     * @return self
     */
    public function auth(string $auth) : self
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Set ruting rules by given callback.
     *
     * @param callable $callback function(){ ... }
     * @return self
     */
    public function routing(callable $callback) : self
    {
        if ($this->skip) {
            return $this;
        }
        static::$rules = $this;
        $callback();
        static::$rules = null;
        return $this;
    }

    /**
     * Set fallback action.
     * The registered action is called when an exception occurs.
     * Normally, it is assumed to be used in log output or error page display.
     *
     * @param callabel $action function(Request $request, ?Route $route, \Throwable $e) { ... }
     * @return void
     */
    public function fallback(callable $action)
    {
        if ($this->skip) {
            return $this;
        }
        static::$fallback[$this->prefix] = $action;
    }
}

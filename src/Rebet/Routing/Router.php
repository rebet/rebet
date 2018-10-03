<?php
namespace Rebet\Routing;

use Rebet\Config\Configurable;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Pipeline\Pipeline;
use Rebet\Config\RouteNotFoundException;
use Rebet\Config\App;
use PHPUnit\Framework\Constraint\IsInstanceOf;

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
            'middlewares'   => [],
            'default_route' => null,
            'fallback'      => null,
        ];
    }

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * ルートリスト
     *
     * @var array
     */
    private static $routes;
    
    /**
     * ルート探査木
     *
     * @var array
     */
    private static $routing_tree;
    
    /**
     * 現在のルート
     *
     * @var Route
     */
    private static $current;
    
    /**
     * ルール定義の最中か否か
     *
     * @var boolean
     */
    private static $in_rules = false;

    /**
     * ルートミドルウェアパイプライン
     * @var Rebet\Pipeline\Pipeline
     */
    private static $pipeline = null;
    
    public static function rules($surface, callable $callback)
    {
        if (in_array(App::getSurface(), (array)$surface)) {
            static::$in_rules = true;
            $callback();
            static::$in_rules = false;
        }
    }

    /**
     * Undocumented function
     *
     * @param array|string $methods
     * @param string $uri
     * @param string|callable $action
     * @return Route
     */
    public static function match($methods, string $uri, $action) : Route
    {
        $route = null;
        if ($action instanceof Route) {
            $route = $action;
        } elseif (is_callable($action)) {
            $route = new ClosureRoute(array_map('strtoupper', (array)$methods), $uri, $action);
        }
        // @todo Controller@method 形式ルートの実装
        
        static::addRoute($route);
        return $route;
    }
    
    /**
     * Register a new GET route with the router.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public function get(string $uri, $action) : Route
    {
        return $this->match(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route with the router.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public function post(string $uri, $action) : Route
    {
        return $this->match('POST', $uri, $action);
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public function put(string $uri, $action) : Route
    {
        return $this->match('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public function patch(string $uri, $action) : Route
    {
        return $this->match('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public function delete(string $uri, $action) : Route
    {
        return $this->match('DELETE', $uri, $action);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public function options(string $uri, $action) : Route
    {
        return $this->match('OPTIONS', $uri, $action);
    }

    /**
     * Register a new route responding to all methods.
     *
     * @param  string  $uri
     * @param  callable|string  $action
     * @return Route
     */
    public function any(string $uri, $action) : Route
    {
        return $this->match([], $uri, $action);
    }
    
    /**
     * ルートをルーターに追加します。
     * 本メソッドはルート解決高速化のための不完全なルート探査木を構築します。
     *
     * @param Route $route
     * @return void
     */
    protected static function addRoute(Route $route)
    {
        if (!static::$in_rules) {
            throw new \LogicException("Routing rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        $this->routes[] = $route;
        
        $nests  = explode('/', Strings::latrim($route->uri, '{'));
        $branch = static::$routing_tree;
        foreach ($nests as $nest) {
            if (Utils::isBlank($nest)) {
                continue;
            }
            if (!isset($branch[$nest])) {
                $branch[$nest] = [];
            }
            $branch = $branch[$nest];
        }
        $branch[':routes:'] = $route;
    }
    
    public static function fallback($action)
    {
        if (!static::$in_rules) {
            throw new \LogicException("Routing fallback rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        static::setConfig(['fallback' => $action]);
    }

    public static function default($route)
    {
        if (!static::$in_rules) {
            throw new \LogicException("Routing default rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        static::setConfig(['default_route' => $route]);
    }

    public static function redirect($uri, $destination, $status = 302)
    {
        //@todo 実装
    }
    
    public static function handle(Request $request) : Response
    {
        $route = null;
        try {
            $route = static::findRoute($request);
            static::$pipeline = (new Pipeline())->through(static::config('middlewares'))->then($route);
            return static::$pipeline->send($request);
        } catch (\Throwable $e) {
            $fallback = static::config("fallback");
            return $fallback($request, $route, $e);
        }
    }
    
    protected static function findRoute(Request $request) : Route
    {
        $base_url     = $request->getBaseUrl();
        $paths        = explode('/', $base_url);
        $routing_tree = static::$routing_tree;
        foreach ($paths as $path) {
            if (!isset($routing_tree[$path])) {
                break;
            }
            $routing_tree = $routing_tree[$path];
        }

        if (!isset($routing_tree[':routes:'])) {
            throw new RouteNotFoundException("Route [{$base_url}] Not Found.");
        }
        foreach ($routing_tree[':routes:'] as $route) {
            if ($route->match($request)) {
                return $route;
            }
        }

        $default_route = static::configInstantiate("default_route", false);
        if ($default_route !== null) {
            return $default_route;
        }

        throw new RouteNotFoundException("Route [{$base_url}] Not Found.");
    }
    
    public static function shutdown(Request $request, Response $response) : void
    {
        if (static::$pipeline !== null) {
            static::$pipeline->invoke('shutdown', $request, $response);
            static::$pipeline->getDestination()->shutdown($request, $response);
        }
    }
}

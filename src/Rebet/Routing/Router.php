<?php
namespace Rebet\Routing;

use Rebet\Config\Configurable;
use Rebet\Common\Strings;
use Rebet\Common\Utils;

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
            'default_route'  => null,
            'fallback_route' => null,
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
     * ルートミドルウェアパイプライン
     * @var Rebet\Pipeline\Pipeline
     */
    private static $pipeline = null;
    
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
        if (is_callable($action)) {
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
    public function post(string $uri, $actionxe) : Route
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
    
    public static function fallback($action) : Route
    {
        //@todo 実装
    }

    public static function redirect($uri, $destination, $status = 302)
    {
        //@todo 実装
    }
}

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
     * @param array $methods
     * @param string $uri
     * @param string|callable $action
     * @return Route
     */
    public static function match(array $methods, string $uri, $action) : Route
    {
        $route = null;
        if (is_callable($action)) {
            $route = new ClosureRoute($methods, $uri, $action);
        }
        // @todo Controller@method 形式ルートの実装
        
        static::addRoute($route);
        return $route;
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

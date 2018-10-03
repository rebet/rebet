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
    
    /**
     * ルーティングルールを設定します。
     *
     * @param string $surface
     * @param callable $callback
     * @return void
     */
    public static function rules(string $surface, callable $callback)
    {
        if ($surface === App::getSurface()) {
            static::$in_rules = true;
            $callback();
            static::$in_rules = false;
        }
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
     * 指定の HTTPメソッド にマッチするルートを設定します。
     * ※HTTPメソッドが無指定（=[]）の場合は全てのメソッドにマッチします。
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
   
    // public static function redirect($uri, $destination, $status = 302)
    // {
    //     //@todo 実装
    // }
    
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
    
    /**
     * フォールバックアクションを設定します。
     * ここで登録したアクションは例外発生時に呼び出されます。
     * 通常、ログ出力やエラーページ表示での利用を想定しています。
     *
     * @param callabel $action function(Request $request, ?Route $route, \Throwable $e) { ... }
     * @return void
     */
    public static function fallback(callable $action)
    {
        if (!static::$in_rules) {
            throw new \LogicException("Routing fallback rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        static::setConfig(['fallback' => $action]);
    }

    /**
     * デフォルトルートを設定します。
     *
     * @param mixed $route Routeオブジェクト 又は それを生成できる instantiate 設定
     * @return void
     */
    public static function default($route)
    {
        if (!static::$in_rules) {
            throw new \LogicException("Routing default rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        static::setConfig(['default_route' => $route]);
    }

    /**
     * 対象のリクエストをルーティングします。
     *
     * @param Request $request
     * @return Response
     */
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
    
    /**
     * リクエストにマッチするルートを検索します。
     *
     * @param Request $request
     * @return Route
     */
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
        if ($default_route !== null && $default_route->match($request)) {
            return $default_route;
        }

        throw new RouteNotFoundException("Route not found for [{$base_url}].");
    }
    
    /**
     * ルーターをシャットダウンします。
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public static function shutdown(Request $request, Response $response) : void
    {
        if (static::$pipeline !== null) {
            static::$pipeline->invoke('shutdown', $request, $response);
            static::$pipeline->getDestination()->shutdown($request, $response);
        }
    }
}

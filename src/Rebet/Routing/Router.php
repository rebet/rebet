<?php
namespace Rebet\Routing;

use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\Foundation\App;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Pipeline\Pipeline;
use Rebet\Routing\RouteNotFoundException;
use Rebet\Common\Reflector;

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
            'middlewares' => [],
        ];
    }

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * ルート探査木
     *
     * @var array
     */
    public static $routing_tree = [];
    
    /**
     * デフォルトルート
     *
     * @var Route
     */
    protected static $default_route = null;

    /**
     * フォールバックアクション
     *
     * @var \Closure
     */
    protected static $fallback = null;

    /**
     * 現在のルート
     *
     * @var Route
     */
    protected static $current = null;
    
    /**
     * ルール定義の最中か否か
     *
     * @var boolean
     */
    protected static $in_rules = false;

    /**
     * ルートミドルウェアパイプライン
     * @var Rebet\Pipeline\Pipeline
     */
    protected static $pipeline = null;
    
    /**
     * Clear all routing rules.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$routing_tree = [];
        static::$current      = null;
        static::$in_rules     = false;
        static::$pipeline     = null;
    }
    
    /**
     * Set new routing rules for given surface.
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
        
        static::addRoute($route);
        return $route;
    }
   
    /**
     * 指定の URI にマッチするコントローラールートを設定します。
     * 詳細なアクセス制御には各種ルーティングアノテーションが利用できます。
     *
     * また、本ルートに設定される where 条件は コントローラースコープ でグローバルな設定となります。
     * 個別のアクション単位で where 条件を設定したい場合は @Where ルーティングアノテーションをご利用下さい。
     *
     * @see Rebet\Routing\Annotation
     *
     * @param string $uri
     * @param string $controller
     * @return Route
     */
    public static function controller(string $uri, string $controller) : Route
    {
        $route = new ControllerRoute($uri, $controller);
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
        static::digging(static::$routing_tree, explode('/', Strings::latrim($route->uri, '{')), $route);
    }
    
    /**
     * ルート探査木を掘り進めながら ルートオブジェクトを格納します。
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
        static::$fallback = $action;
    }

    /**
     * デフォルトルートを設定します。
     *
     * @param mixed $route Routeオブジェクト 又は それを生成できる instantiate 設定
     * @return void
     */
    public static function default($route) : Route
    {
        if (!static::$in_rules) {
            throw new \LogicException("Routing default rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        static::$default_route = Reflector::instantiate($route);
        return static::$default_route;
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
            static::$current  = $route;
            static::$pipeline = (new Pipeline())->through(static::config('middlewares', false, []))->then($route);
            return static::$pipeline->send($request);
        } catch (\Throwable $e) {
            $fallback = static::$fallback;
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
        $request_uri  = $request->getRequestUri();
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

        if (static::$default_route !== null && static::$default_route->match($request)) {
            return static::$default_route;
        }

        throw new RouteNotFoundException("Route {$request->getMethod()} {$request_uri} not found.");
    }
    
    /**
     * ルーターをシャットダウンします。
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
     * 現在ルーティングされているルートを取得します。
     *
     * @return Route|null
     */
    public static function current() : ?Route
    {
        return static::$current;
    }
}

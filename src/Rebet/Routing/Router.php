<?php
namespace Rebet\Routing;

use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\File\Files;
use Rebet\Foundation\App;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Pipeline\Pipeline;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Route\ControllerRoute;
use Rebet\Routing\Route\MethodRoute;
use Rebet\Routing\Route\Route;

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
     * ルート探査木
     *
     * @var array
     */
    public static $routing_tree = [];
    
    /**
     * Default Route
     *
     * @var array [prefix => Route]
     */
    protected static $default_route = [];

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
     * Current routing rule builder.
     *
     * @var Router
     */
    protected static $rules = null;

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
        static::$routing_tree  = [];
        static::$current       = null;
        static::$rules         = null;
        static::$pipeline      = null;
        static::$default_route = [];
        static::$fallback      = null;
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
        if (!static::$rules) {
            throw new \LogicException("Routing rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        $route->prefix = static::$rules->prefix;
        static::digging(static::$routing_tree, explode('/', Strings::latrim($route->prefix.$route->uri, '{')), $route);
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
     * デフォルトルートを設定します。
     *
     * @param mixed $route Routeオブジェクト 又は それを生成できる instantiate 設定
     * @return void
     */
    public static function default($route) : Route
    {
        if (!static::$rules) {
            throw new \LogicException("Routing default rules are defined without Router::rules(). You should wrap rules by Router::rules().");
        }
        $route         = Reflector::instantiate($route);
        $route->prefix = static::$rules->prefix;

        static::$default_route[$route->prefix] = $route;
        return $route;
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
            $route            = static::findRoute($request);
            static::$current  = $route;
            static::$pipeline = (new Pipeline())->through(static::config('middlewares', false, []))->then($route);
            return static::$pipeline->send($request);
        } catch (\Throwable $e) {
            if (empty(static::$fallback)) {
                throw $e;
            }

            $root_fallback = null;
            $request_uri   = $request->getRequestUri();
            foreach (static::$fallback as $prefix => $fallback) {
                if ($prefix === '') {
                    $root_fallback = $fallback;
                }
                if (Strings::startsWith($request_uri, "{$prefix}/")) {
                    return $fallback($request, $route, $e);
                }
            }
            if ($root_fallback) {
                return $root_fallback($request, $route, $e);
            }

            throw $e;
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

        // if (static::$default_route !== null && static::$default_route->match($request)) {
        //     return static::$default_route;
        // }

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

    // ====================================================
    // Router instance for Routing Rules Build
    // ====================================================

    /**
     * The surface for current configuring routing rules.
     *
     * @var string
     */
    protected $surface = null;

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
     * Create a routing rules builder for Router.
     */
    protected function __construct(string $surface)
    {
        $this->surface = $surface;
        $this->skip    = $surface !== App::getSurface();
    }

    /**
     * Set new routing rules for given surface.
     *
     * @param string $surface
     * @return void
     */
    public static function rules(string $surface) : self
    {
        return new static($surface);
    }
    
    /**
     * Set the prefix path for this rules.
     *
     * @param string $prefix
     * @return self
     */
    public function prefix(string $prefix) : self
    {
        $this->prefix = Files::normalizePath($prefix);
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

<?php
namespace Rebet\Routing;

use Rebet\Common\Inflector;
use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\Config\Config;
use Rebet\Foundation\App;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\StreamedResponse;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\Surface;
use Rebet\Routing\Annotation\Where;
use Rebet\Annotation\AnnotatedMethod;

/**
 * Conventional Route class
 *
 * 規約ベースのルートオブジェクト
 * 以下のパターンでURL解析を行い
 *
 * 　http://domain.of.yours/{controller}/{action}/{arg1}/{arg2}...
 * 　例1) /user/detail/123456
 * 　例1) /user/register-input
 * 　例3) /term
 *
 * 以下の処理を実行します。
 *
 * 　{Controller}@{action}({arg1}, {arg2}, ...)
 * 　例1) UserController@detail(123456)
 * 　例2) UserController@registerInput()
 * 　例3) TermController@index()
 *
 * controller : コントローラー名（デフォルト：Top）
 * action     : アクション名（デフォルト：index）
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConventionalRoute extends Route
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'namespace'                  => null,
            'default_part_of_controller' => 'top',
            'default_part_of_action'     => 'index',
            'uri_snake_separator'        => '-',
            'controller_suffix'          => 'Controller',
            'action_suffix'              => '',
            'accessible'                 => false,
        ];
    }

    /**
     * 名前空間
     *
     * @var string
     */
    protected $namespace = null;

    /**
     * デフォルトコントローラーパート名（=top）
     *
     * @var string
     */
    protected $default_part_of_controller = null;

    /**
     * デフォルトアクションパート名（=index）
     *
     * @var string
     */
    protected $default_part_of_action = null;

    /**
     * URIスネークケース区切り文字（=ハイフン['-']）
     *
     * @var string
     */
    protected $uri_snake_separator = null;

    /**
     * コントローラークラス名サフィックス（=Controller）
     *
     * @var string
     */
    protected $controller_suffix = null;

    /**
     * アクションメソッド名サフィックス（=空文字['']）
     *
     * @var string
     */
    protected $action_suffix = null;
    
    /**
     * 非公開メソッドへのアクセス許可
     *
     * @var boolean
     */
    protected $accessible = false;

    /**
     * 解析されたコントローラーパート文字列
     *
     * @var string
     */
    protected $part_of_controller = null;

    /**
     * 解析されたアクションパート文字列
     *
     * @var string
     */
    protected $part_of_action = null;

    /**
     * コントローラーオブジェクト
     *
     * @var Controller
     */
    protected $controller = null;

    /**
     * ルートオブジェクトを構築します
     *
     * @param array  $option [
     *     'namespace'                  => refer App config 'namespace.controller',
     *     'default_part_of_controller' => 'top',
     *     'default_part_of_action'     => 'index',
     *     'uri_snake_separator'        => '-',
     *     'controller_suffix'          => 'Controller',
     *     'action_suffix'              => '',
     *     'accessible'                 => false
     * ]
     */
    public function __construct(array $option = [])
    {
        $this->namespace                  = $option['amespace']                   ?? static::config('namespace');
        $this->default_part_of_controller = $option['default_part_of_controller'] ?? static::config('default_part_of_controller');
        $this->default_part_of_action     = $option['default_part_of_action']     ?? static::config('default_part_of_action');
        $this->uri_snake_separator        = $option['uri_snake_separator']        ?? static::config('uri_snake_separator');
        $this->controller_suffix          = $option['controller_suffix']          ?? static::config('controller_suffix', false, '');
        $this->action_suffix              = $option['action_suffix']              ?? static::config('action_suffix', false, '');
        $this->accessible                 = $option['accessible']                 ?? static::config('accessible');
    }

    /**
     * リクエストURIを コントローラー名／アクション名／引数 に分解します。
     *
     * @param string $request_uri
     * @return array
     */
    protected function resolveRequestUri(string $request_uri) : array
    {
        $requests = explode(trim($request_uri, '/')) ;
        $part_of_controller = array_shift($requests) ?: $this->default_part_of_controller;
        $part_of_action     = array_shift($requests) ?: $this->default_part_of_action;
        $args               = $requests;
        return [$part_of_controller, $part_of_action, $args];
    }

    /**
     * 指定のリクエストを解析し、自身のルートにマッチするか解析します。
     * 解析の過程で取り込んだルーティングパラメータを返します。
     *
     * 解析結果として null を返すと後続のルート検証が行われます。
     * 後続のルート検証を行わない場合は RouteNotFoundException を throw して下さい。
     *
     * @param Request $request
     * @return array|null
     * @throws RouteNotFoundException
     */
    protected function analyze(Request $request) : ?array
    {
        [$this->part_of_controller, $this->part_of_action, $args] = $this->resolveRequestUri($request->getRequestUri());

        $controller = $this->getControllerName();
        try {
            $this->controller = new $controller();
            $this->controller->request = $request;
            $this->controller->route   = $this;
        } catch (\Throwable $e) {
            throw new RouteNotFoundException("Route not found : Controller [ {$controller} ] can not instantiate.", null, $e);
        }

        $action = $this->getActionName();
        $method = null;
        try {
            $method = new \ReflectionMethod($controller, $action);
            $method->setAccessible($this->accessible);
        } catch (\Throwable $e) {
            throw new RouteNotFoundException("Route not found : Action [ {$controller}::{$action} ] not exists.", null, $e);
        }
        if (!$this->accessible && !$method->isPublic()) {
            throw new RouteNotFoundException("Route not found : Action [ {$controller}::{$action} ] not accessible.", null, $e);
        }

        $vars = [];
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!$parameter->isOptional() && empty($args)) {
                throw new RouteNotFoundException("Route not found : Requierd parameter '{$name}' on [ {$controller}::{$action} ] not supplied.", null, $e);
            }
            if (empty($args)) {
                break;
            }
            $value  = array_shift($args);
            $wheres = Reflector::get(AnnotatedMethod::of($method)->annotation(Where::class, true), 'wheres', []);
            $regex  = $wheres[$name] ?: $this->wheres[$name] ?: null ;
            if ($regex && !preg_match($regex, $value)) {
                throw new RouteNotFoundException("{$this} not found. Routing parameter '{$name}' value '{$value}' not match {$regex}.");
            }
            $vars[$name] = $value;
        }
        
        return $vars;
    }

    /**
     * analyze によってマッチしたリクエストを処理するための ルートアクション を返します。
     * サブクラスではここで追加のアノテーション検証などを行うことができます。
     *
     * 追加検証でルーティング対象外となる場合は RouteNotFoundException を throw して下さい。
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    protected function createRouteAction(Request $request) : RouteAction
    {
        $method = new \ReflectionMethod($this->controller, $this->getActionName());
        $method->setAccessible($this->accessible);
        $route_action = new RouteAction($this, $method, $this->controller);

        $surface = $route_action->annotation(Surface::class);
        if (!$surface || $surface->reject(App::getSurface())) {
            throw new RouteNotFoundException("{$this} not found. Routing surface '".App::getSurface()."' not allowed or not annotated surface meta info.");
        }

        $method = $route_action->annotation(Method::class);
        if ($method && $method->reject($request->getMethod())) {
            throw new RouteNotFoundException("{$this} not found. Routing method '{$request->getMethod()}' not allowed.");
        }

        return $route_action;
    }

    /**
     * コントローラー名を取得します。
     *
     * @param bool $with_namespace
     * @return string
     */
    public function getControllerName(bool $with_namespace = true) : string
    {
        $namespace = $with_namespace ? $this->namespace.'\\' : '' ;
        return $namespace.Inflector::camelize($this->part_of_controller, $this->uri_snake_separator).$this->controller_suffix;
    }

    /**
     * アクション名を取得します。
     *
     * @return string
     */
    public function getActionName() : string
    {
        return Inflector::methodize($this->part_of_action).$this->action_suffix;
    }

    /**
     * シャットダウン処理を行います。
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response) : void
    {
        // Do Nothing.
    }

    /**
     * 文字列化します。
     *
     * @return string
     */
    public function __toString()
    {
        return "Route: {$this->getControllerName()}::{$this->getActionName()}";
    }

    /**
     * 非公開メソッドへのアクセス制御を設定します。
     *
     * @param boolean $accessible
     * @return self
     */
    public function accessible(bool $accessible) : self
    {
        $this->accessible = $accessible;
        return $this;
    }
}

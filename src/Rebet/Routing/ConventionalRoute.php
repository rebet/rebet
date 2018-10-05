<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\StreamedResponse;
use Rebet\Common\Inflector;
use Rebet\Routing\Annotation\Where;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\Surface;
use Rebet\Config\App;
use Rebet\Config\Configurable;
use Rebet\Config\Config;

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
        parent::__construct([], null);
        $this->namespace                  = $option['amespace']                   ?? static::config('namespace');
        $this->default_part_of_controller = $option['default_part_of_controller'] ?? static::config('default_part_of_controller');
        $this->default_part_of_action     = $option['default_part_of_action']     ?? static::config('default_part_of_action');
        $this->uri_snake_separator        = $option['uri_snake_separator']        ?? static::config('uri_snake_separator');
        $this->controller_suffix          = $option['controller_suffix']          ?? static::config('controller_suffix');
        $this->action_suffix              = $option['action_suffix']              ?? static::config('action_suffix');
        $this->accessible                 = $option['accessible']                 ?? static::config('accessible');
    }

    /**
     * 対象のリクエストが規約ベースのルート設定にマッチするかチェックします。
     * 本メソッドは 規約に従った URI 指定を解析し、マッチ結果を返します。
     * なお、マッチングの過程で取り込まれたルーティングパラメータは $request->attributes に格納されます。
     *
     * マッチ結果として false を返すと後続のルート検証が行われます。
     * 後続のルート検証を行わない場合は RouteNotFoundException を throw して下さい。
     *
     * @param Request $request
     * @return boolean
     */
    public function match(Request $request) : bool
    {
        $requests = explode(trim($request->getRequestUri(), '/')) ;
        $part_of_controller = array_shift($requests) ?: $this->default_part_of_controller;
        $part_of_action     = array_shift($requests) ?: $this->default_part_of_action;
        $args               = $requests;
        return [$part_of_controller, $part_of_action, $args];

        $controller = $this->getControllerName();
        try {
            $this->controller = new $controller();
            $this->controller->request = $request;
            $this->controller->route   = $this;
        } catch (Throwable $e) {
            throw new RouteNotFoundException("Route not found : Controller [ {$controller} ] can not instantiate.", null, $e);
        }

        $action = $this->getActionName();
        $method = null;
        try {
            $method = new \ReflectionMethod($controller, $action);
            $method->setAccessible($this->accessible);
        } catch (Throwable $e) {
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
            $wheres = Utils::get($this->annotation(Where::class), 'wheres', []);
            $regex  = $wheres[$key] ?: $this->wheres[$key] ?: null ;
            if ($regex && !preg_match($regex, $value)) {
                throw new RouteNotFoundException("{$this} not found. Routing parameter '{$key}' value '{$value}' not match {$regex}.");
            }
            $vars[$name] = $value;
        }

        $request->attributes->add($vars);
        $this->route_action = $this->createRouteAction($request);
        $request->route = $this;

        $surface = $this->annotation(Surface::class);
        if ($surface || $surface->reject(App::getSurface())) {
            throw new RouteNotFoundException("{$this} not found. Routing surface '".App::getSurface()."' not allowed or not annotated surface meta info.");
        }

        $method = $this->annotation(Method::class);
        if ($method && $method->reject($request->getMethod())) {
            throw new RouteNotFoundException("{$this} not found. Routing method '{$request->getMethod()}' not allowed.");
        }

        return true;
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
     * 実行可能な RouteAction を作成します。
     *
     * @param Request $request
     * @return RouteAction
     */
    protected function createRouteAction(Request $request) : RouteAction
    {
        return new RouteAction($this, new \ReflectionMethod($this->controller, $this->getActionName()), $this->accessible, $this->controller);
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
        return "Route: {$this->getControllerName()}::{$this->getActionName}";
    }
}

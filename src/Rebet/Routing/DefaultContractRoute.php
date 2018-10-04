<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\StreamedResponse;
use Rebet\Common\Inflector;

/**
 * Default Contract Route class
 *
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
class DefaultContractRoute extends Route
{
    /**
     * 名前空間
     *
     * @var string
     */
    protected $namespace = null;

    /**
     * デフォルトコントローラー名（=top）
     *
     * @var string
     */
    protected $default_controller = null;

    /**
     * デフォルトアクション名（=index）
     *
     * @var string
     */
    protected $default_action = null;

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
     * 解析かれたルーティングパラメータ配列
     *
     * @var array
     */
    protected $args = [];

    /**
     * ルートオブジェクトを構築します
     *
     * @param string $namespace
     * @param array  $option [
     *     'default_controller'  => 'top',
     *     'default_action'      => 'index',
     *     'uri_snake_separator' => '-',
     *     'controller_suffix'   => 'Controller',
     *     'action_suffix'       => '',
     *     'accessible'          => false
     * ]
     */
    public function __construct(string $namespace, array $option)
    {
        parent::__construct([], null);
        $this->namespace           = $namespace;
        $this->default_controller  = $option['default_controller']  ?? 'top';
        $this->default_action      = $option['default_action']      ?? 'index';
        $this->uri_snake_separator = $option['uri_snake_separator'] ?? '-';
        $this->controller_suffix   = $option['controller_suffix']   ?? 'Controller';
        $this->action_suffix       = $option['action_suffix']       ?? '';
        $this->accessible          = $option['accessible']          ?? false;
    }

    /**
     * サポートされていません。
     *
     * @param string|array $name
     * @param string|null $regex
     * @return self
     * @throws BadMethodCallException
     */
    public function where($name, ?string $regex = null) : self
    {
        throw new \BadMethodCallException("Contract based route not support where check.");
    }

    /**
     *
     *
     * @param Request $request
     * @return boolean
     */
    public function match(Request $request) : bool
    {
        $requests = explode(trim($request->getRequestUri(), '/')) ;
        $this->part_of_controller = array_shift($requests) ?: $this->default_controller;
        $this->part_of_action     = array_shift($requests) ?: $this->default_action;
        $this->args               = $requests;

        try {
            return new $controller();
        } catch (Throwable $e) {
            throw new NoRouteException("Route Not Found : Controller [ {$controller} ] can not instantiate.", null, $e);
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
        // @todo 実装
        return null;
        // return Inflector::
    }

    /**
     * Router によってマッチングされたルートが本当に処理可能か検証し、
     * 問題がなければ実行可能な RouteAction を返します。
     * ある種の Route ではアノテーションを用いた追加検証などを実施することができます。
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    public function verify(Request $request) : RouteAction
    {
        return new RouteAction($this, new \ReflectionFunction($this->action));
    }
    
    /**
     * シャットダウン処理を行います。
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function shutdown(Request $request, Response $response) : void
    {
        // Do Nothing.
    }
}

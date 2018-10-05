<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\StreamedResponse;
use Rebet\Config\Configurable;
use Rebet\Config\Config;
use Rebet\Config\App;

/**
 * MethodRoute class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MethodRoute extends DeclarativeRoute
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'namespace' => null,
        ];
    }

    /**
     * 名前空間
     *
     * @var string
     */
    protected $namespace = null;

    /**
     * メソッドアクション
     *
     * @var \ReflectionMethod
     */
    protected $action = null;
    
    /**
     * コントローラオブジェクト
     *
     * @var Controller
     */
    protected $controller = null;

    /**
     * ルートオブジェクトを構築します
     *
     * @param array $methods
     * @param string $uri
     * @param string $action 'Controller + @ + method'
     * @param string $namespace default config is refer App config 'namespace.controller'
     * @throws ReflectionException
     */
    public function __construct(array $methods, string $uri, string $action, string $namespace = null)
    {
        parent::__construct($methods, $uri);
        $action = str_replace('@', '::', $action);
        $this->namespace = $namespace ?? static::config('namespace', false, '') ;
        try {
            $this->action    = new \ReflectionMethod($action);
            $this->namespace = $this->action->getNamespaceName();
        } catch (\ReflectionException $e) {
            $this->action = new \ReflectionMethod($this->namespace.'\\'.$action);
        }
    }

    /**
     * 実行可能な RouteAction を作成します。
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    public function createRouteAction(Request $request) : RouteAction
    {
        $this->controller = $this->action->getDeclaringClass()->newInstance();
        return new RouteAction($this, $this->action, $this->controller);
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
}

<?php
namespace Rebet\Routing\Route;

use Rebet\Config\App;
use Rebet\Config\Config;
use Rebet\Config\Configurable;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Routing\RouteAction;

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
     * アクセス制御
     *
     * @var boolean
     */
    protected $accessible = false;
    
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
        $action          = str_replace('@', '::', $action);
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
    protected function createRouteAction(Request $request) : RouteAction
    {
        $this->controller = $this->action->getDeclaringClass()->newInstance();
        $this->action->setAccessible($this->accessible);
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

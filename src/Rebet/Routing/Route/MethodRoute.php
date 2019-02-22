<?php
namespace Rebet\Routing\Route;

use Rebet\Common\Namespaces;
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
            'namespace' => '@controller',
        ];
    }

    /**
     * @var \ReflectionMethod
     */
    protected $action = null;
    
    /**
     * @var Controller
     */
    protected $controller = null;

    /**
     * @var boolean
     */
    protected $accessible = false;
    
    /**
     * Create Route instance.
     *
     * @param array $methods
     * @param string $uri
     * @param string $action 'Namespace\\Controller::method'. The namespace can be use @ namespace alias.
     * @throws ReflectionException
     */
    public function __construct(array $methods, string $uri, string $action)
    {
        parent::__construct($methods, $uri);
        try {
            $this->action = new \ReflectionMethod(Namespaces::resolve($action));
        } catch (\ReflectionException $e) {
            $this->action = new \ReflectionMethod(Namespaces::resolve(static::config('namespace', false, '').'\\'.$action));
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
        $this->controller = null;
        if (!$this->action->isStatic()) {
            $this->controller          = $this->action->getDeclaringClass()->newInstance();
            $this->controller->request = $request;
            $this->controller->route   = $this;
        }
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

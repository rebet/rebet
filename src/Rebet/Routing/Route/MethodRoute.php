<?php
namespace Rebet\Routing\Route;

use Rebet\Common\Namespaces;
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
     * Accessible to non public member action(method).
     *
     * @var boolean
     */
    protected $accessible = false;

    /**
     * Create Route instance.
     *
     * @param array $methods
     * @param string $uri
     * @param string $action 'Namespace\\Controller::method'. The namespace can be use @ namespace alias. (default namespace: depend on configure)
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
     * Cleate a route action for this route.
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
     * Terminate the route.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response) : void
    {
        if (method_exists($this->controller, 'terminate')) {
            $this->controller->terminate($request, $response);
        }
    }

    /**
     * Set access control to non public contorller methods.
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

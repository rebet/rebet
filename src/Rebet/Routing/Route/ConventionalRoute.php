<?php
namespace Rebet\Routing\Route;

use Rebet\Annotation\AnnotatedMethod;
use Rebet\Common\Namespaces;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Inflection\Inflector;
use Rebet\Routing\Annotation\AliasOnly;
use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\NotRouting;
use Rebet\Routing\Annotation\Where;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\RouteAction;
use Rebet\Routing\Router;

/**
 * Conventional Route class
 *
 * Perform URL analysis with the following pattern,
 *
 * 　http://domain.of.yours[/{prefix}]/{controller}/{action}/{arg1}/{arg2}...
 * 　ex1) /user/detail/123456
 * 　ex1) /user/register-input
 * 　ex3) /term
 *
 * Perform the following processing.
 *
 * 　{Controller}@{action}({arg1}, {arg2}, ...)
 * 　ex1) UserController@detail(123456)
 * 　ex2) UserController@registerInput()
 * 　ex3) TermController@index()
 *
 * controller : controller name (default: top)
 * action     : action name (default: index)
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
            'namespace'                  => '@controller',
            'default_part_of_controller' => 'top',
            'default_part_of_action'     => 'index',
            'uri_snake_separator'        => '-',
            'controller_suffix'          => 'Controller',
            'action_suffix'              => '',
            'aliases'                    => [],
            'accessible'                 => false,
        ];
    }

    /**
     * Namespace of controller.
     *
     * @var string
     */
    protected $namespace = null;

    /**
     * Default URI part of controller (default: top)
     *
     * @var string
     */
    protected $default_part_of_controller = null;

    /**
     * Default URI part of action (default: index)
     *
     * @var string
     */
    protected $default_part_of_action = null;

    /**
     * URI snake separator (default: '-')
     *
     * @var string
     */
    protected $uri_snake_separator = null;

    /**
     * Controller name suffix (default: Controller)
     *
     * @var string
     */
    protected $controller_suffix = null;

    /**
     * Action name suffix (default: '')
     *
     * @var string
     */
    protected $action_suffix = null;

    /**
     * Accessible to non public member action(method).
     *
     * @var boolean
     */
    protected $accessible = false;

    /**
     * Routing aliases map.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Name via alias.
     *
     * @var string
     */
    protected $alias = null;

    /**
     * Analized part of controller.
     *
     * @var string
     */
    protected $part_of_controller = null;

    /**
     * Analized part of action.
     *
     * @var string
     */
    protected $part_of_action = null;

    /**
     * Instance of controller.
     *
     * @var Controller
     */
    protected $controller = null;

    /**
     * Create a conventional route.
     *
     * @param array  $option [
     *     'namespace'                  => '@controller', // can be use @ namespace alias
     *     'default_part_of_controller' => 'top',
     *     'default_part_of_action'     => 'index',
     *     'uri_snake_separator'        => '-',
     *     'controller_suffix'          => 'Controller',
     *     'action_suffix'              => '',
     *     'aliases'                    => [],
     *     'accessible'                 => false,
     * ]
     */
    public function __construct(array $option = [])
    {
        $this->namespace                  = Namespaces::resolve($option['namespace'] ?? static::config('namespace'));
        $this->default_part_of_controller = $option['default_part_of_controller'] ?? static::config('default_part_of_controller');
        $this->default_part_of_action     = $option['default_part_of_action'] ?? static::config('default_part_of_action');
        $this->uri_snake_separator        = $option['uri_snake_separator'] ?? static::config('uri_snake_separator');
        $this->controller_suffix          = $option['controller_suffix'] ?? static::config('controller_suffix', false, '');
        $this->action_suffix              = $option['action_suffix'] ?? static::config('action_suffix', false, '');
        $this->aliases                    = $option['aliases'] ?? static::config('aliases', false, []);
        $this->accessible                 = $option['accessible'] ?? static::config('accessible');
    }

    /**
     * Resolve request URI into controller name / action name / arguments.
     *
     * @param string $request_uri
     * @return array
     */
    protected function resolveRequestUri(string $request_uri) : array
    {
        $requests           = explode('/', trim($request_uri, '/')) ;
        $part_of_controller = array_shift($requests) ?: $this->default_part_of_controller;
        $part_of_action     = array_shift($requests) ?: $this->default_part_of_action;
        $args               = $requests;
        return [$part_of_controller, $part_of_action, $args];
    }

    /**
     * It analyzes the given request and analyzes whether it matches this route.
     * Returns the routing parameters captured during the analysis process.
     *
     * If null is returned as an analysis result, subsequent route verification is performed.
     * Throw RouteNotFoundException if subsequent route verification is not done.
     *
     * @param Request $request
     * @return array|null
     * @throws RouteNotFoundException
     */
    protected function analyze(Request $request) : ?array
    {
        $request_uri = Strings::ltrim($request->getRequestPath(), $this->prefix, 1);
        foreach ($this->aliases as $alias => $path) {
            if (Strings::startsWith($request_uri, $alias)) {
                $request_uri = str_replace($alias, $path, $request_uri);
                $this->alias = $alias;
                break;
            }
        }
        [$this->part_of_controller, $this->part_of_action, $args] = $this->resolveRequestUri($request_uri);

        $controller = $this->getControllerName();
        try {
            $this->controller          = new $controller();
            $this->controller->request = $request;
            $this->controller->route   = $this;
        } catch (\Throwable $e) {
            throw RouteNotFoundException::by("Route not found : Controller [ {$controller} ] can not instantiate.")->caused($e);
        }

        $action = $this->getActionName();
        $method = null;
        try {
            $method = new \ReflectionMethod($controller, $action);
            $method->setAccessible($this->accessible);
        } catch (\Throwable $e) {
            throw RouteNotFoundException::by("Route not found : Action [ {$controller}::{$action} ] not exists.")->caused($e);
        }
        if (!$this->accessible && !$method->isPublic()) {
            throw RouteNotFoundException::by("Route not found : Action [ {$controller}::{$action} ] not accessible.");
        }

        $am   = AnnotatedMethod::of($method);
        if ($am->annotation(NotRouting::class)) {
            throw RouteNotFoundException::by("Route not found : Action [ {$controller}::{$action} ] is not routing.");
        }
        if ($am->annotation(AliasOnly::class) && !$this->alias) {
            throw RouteNotFoundException::by("Route not found : Action [ {$controller}::{$action} ] accespt only alias access.");
        }
        $wheres = Reflector::get($am->annotation(Where::class), 'wheres', []);
        $vars   = [];
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!$parameter->isOptional() && empty($args)) {
                throw RouteNotFoundException::by("Route not found : Requierd parameter '{$name}' on [ {$controller}::{$action} ] not supplied.");
            }
            if (empty($args)) {
                break;
            }
            $value = array_shift($args);

            $regex = $wheres[$name] ?? null ;
            $regex = $regex ?: $this->wheres[$name] ?? null;
            $regex = $regex ?: null;

            if ($regex && !preg_match($regex, $value)) {
                throw RouteNotFoundException::by("{$this} not found. Routing parameter '{$name}' value '{$value}' not match {$regex}.");
            }
            $vars[$name] = $value;
        }

        return $vars;
    }

    /**
     * Returns the route action for processing the request matched.
     * For subclasses, additional annotation verification etc. can be done here.
     *
     * If routing is not performed by additional verification, please throw RouteNotFoundException.
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

        $channel = $route_action->annotation(Channel::class);
        if (!$channel || $channel->reject(Router::getCurrentChannel())) {
            throw RouteNotFoundException::by("{$this} not found. Routing channel '".Router::getCurrentChannel()."' not allowed or not annotated channel meta info.");
        }

        $method = $route_action->annotation(Method::class);
        if ($method && $method->reject($request->getMethod())) {
            throw RouteNotFoundException::by("{$this} not found. Routing method '{$request->getMethod()}' not allowed.");
        }

        return $route_action;
    }

    /**
     * {@inheritDoc}
     */
    public function defaultView() : string
    {
        return "/{$this->part_of_controller}/{$this->part_of_action}";
    }

    /**
     * Get matched controller name
     *
     * @param bool $with_namespace
     * @return string
     */
    public function getControllerName(bool $with_namespace = true) : string
    {
        $namespace = $with_namespace ? $this->namespace.'\\' : '' ;
        return $namespace.Inflector::pascalize($this->part_of_controller, $this->uri_snake_separator).$this->controller_suffix;
    }

    /**
     * Get matched action name
     *
     * @return string
     */
    public function getActionName() : string
    {
        return Inflector::camelize($this->part_of_action).$this->action_suffix;
    }

    /**
     * Get alias name if the route via alias.
     *
     * @return string|null
     */
    public function getAliasName() : ?string
    {
        return $this->alias;
    }

    /**
     * Terminame the route.
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
     * {@inheritDoc}
     */
    public function __toString()
    {
        return "Route: {$this->getControllerName()}::{$this->getActionName()}";
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

    /**
     * Set aliases.
     *
     * @param array|string $alias or [$alias => $path, ...]
     * @param string|null $path
     * @return self
     */
    public function aliases($alias, ?string $path = null) : self
    {
        foreach (is_array($alias) ? $alias : [$alias => $path] as $key => $value) {
            $this->aliases[$key] = $value;
        }
        return $this;
    }
}

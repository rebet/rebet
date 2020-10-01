<?php
namespace Rebet\Routing\Route;

use Rebet\Tools\Utility\Strings;
use Rebet\Http\Request;
use Rebet\Inflection\Inflector;

/**
 * Controller Route Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ControllerRoute extends ConventionalRoute
{
    public static function defaultConfig()
    {
        return static::shareConfigWith(parent::class);
    }

    /**
     * Target uri for routing
     *
     * @var string
     */
    public $uri = null;

    /**
     * Controller action
     *
     * @var \ReflectionClass
     */
    protected $action = null;

    /**
     * Create a controller route.
     *
     * @param string $uri
     * @param string $controller
     * @throws ReflectionException
     */
    public function __construct(string $uri, string $controller)
    {
        parent::__construct([]);
        $this->uri = $uri;
        try {
            $this->action    = new \ReflectionClass($controller);
            $this->namespace = $this->action->getNamespaceName();
        } catch (\ReflectionException $e) {
            $this->action    = new \ReflectionClass($this->namespace.'\\'.$controller);
        }
    }

    /**
     * Resolve request URI into controller name / action name / arguments.
     *
     * @param string $request_uri
     * @return array
     */
    protected function resolveRequestUri(string $request_uri) : array
    {
        $request_uri        = Strings::ltrim($request_uri, $this->uri, 1);
        $requests           = explode('/', trim($request_uri, '/')) ;
        $part_of_controller = Inflector::snakize(Strings::rtrim($this->action->getShortName(), $this->controller_suffix, 1), $this->uri_snake_separator);
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
        $uri         = rtrim($this->uri, '/');
        if ($request_uri !== $uri && !Strings::startsWith($request_uri, "{$uri}/")) {
            return null;
        }

        return parent::analyze($request);
    }

    /**
     * Get controller name.
     *
     * @param bool $with_namespace (default: true)
     * @return string
     */
    public function getControllerName(bool $with_namespace = true) : string
    {
        return $with_namespace ? $this->action->getName() : $this->action->getShortName() ;
    }
}

<?php
declare(strict_types=1);

namespace Rebet\Routing;

use Rebet\Attribute\AttributedMethod;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Route\Route;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Strings;

/**
 * Route Action Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RouteAction
{
    /**
     * @var Route of this action
     */
    private $route;

    /**
     * Instance to be action executed
     *
     * @var mixed
     */
    private $instance = null;

    /**
     * Action Reflector
     *
     * @var \ReflectionFunction|\ReflectionMethod
     */
    private $reflector;

    /**
     * Method attribute accessor
     *
     * @var AttributedMethod|null
     */
    private $attributed_method = null;

    /**
     * Create a route action object
     *
     * @param Route $route
     * @param \ReflectionFunction|\ReflectionMethod $reflector
     * @param mixed $instance
     */
    public function __construct(Route $route, \ReflectionFunction|\ReflectionMethod $reflector, $instance = null)
    {
        $this->route             = $route;
        $this->reflector         = $reflector;
        $this->instance          = $instance;
        $this->attributed_method = $this->isFunction() ? null : AttributedMethod::of($reflector);
    }

    /**
     * Invoke this action
     *
     * @param Request $request
     * @return Response
     */
    public function invoke(Request $request) : Response
    {
        if ($this->instance && method_exists($this->instance, 'before')) {
            $request = $this->instance->before($request);
        }

        $vars = $request->attributes;
        $args = [];
        foreach ($this->reflector->getParameters() as $parameter) {
            $name = $parameter->name;
            $type = Reflector::getTypeHint($parameter);
            if ($type !== null && Reflector::typeOf($request, $type)) {
                $args[$name] = $request;
                continue;
            }
            $optional      = $parameter->isOptional();
            $default_value = $optional ? $parameter->getDefaultValue() : null ;
            $origin        = $vars->has($name) ? $vars->get($name) : $default_value ;
            if (!$optional && $origin === null) {
                throw new RouteNotFoundException("{$this->route} not found. Routing parameter '{$name}' is requierd.");
            }
            $converted = Reflector::convert($origin, $type);
            if ($origin !== null && $converted === null) {
                throw new RouteNotFoundException("{$this->route} not found. Routing parameter {$name}(=".Strings::stringify($origin).") can not convert to {$type}.");
            }
            $args[$name] = $converted;
        }

        $response = $this->isFunction() ? $this->reflector->invokeArgs($args) : $this->reflector->invokeArgs($this->instance, $args);
        $response = Responder::toResponse($response, 200, [], $request);
        return $this->instance && method_exists($this->instance, 'after') ? $this->instance->after($request, $response) : $response;
    }

    /**
     * It checks the reflector is ReflectionFunction.
     *
     * @return boolean
     */
    protected function isFunction() : bool
    {
        return $this->reflector instanceof \ReflectionFunction;
    }

    /**
     * It checks the reflector is ReflectionMethod.
     *
     * @return boolean
     */
    protected function isMethod() : bool
    {
        return $this->reflector instanceof \ReflectionMethod;
    }

    /**
     * Get method attribute accessor of this route action.
     *
     * @return AttributedMethod|null
     */
    public function getAttributedMethod() : AttributedMethod|null
    {
        return $this->attributed_method;
    }

    /**
     * Get given attribute of this route action.
     *
     * @param string $attribute
     * @return mixed
     */
    public function attribute(string $attribute)
    {
        return $this->attributed_method ? $this->attributed_method->attribute($attribute) : null ;
    }
}

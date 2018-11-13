<?php
namespace Rebet\Routing;

use Rebet\Annotation\AnnotatedMethod;
use Rebet\Common\Reflector;
use Rebet\Http\Request;
use Rebet\Routing\Route\Route;

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
     * Route of this action
     *
     * @var Route
     */
    private $route = null;
    
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
    private $reflector = null;

    /**
     * Method annotation accessor
     *
     * @var AnnotatedMethod
     */
    private $annotated_method = null;

    /**
     * Create a route action object
     *
     * @param Route $route
     * @param \ReflectionFunction|\ReflectionMethod $reflector
     * @param $mixed $instance
     */
    public function __construct(Route $route, $reflector, $instance = null)
    {
        if (!($reflector instanceof \ReflectionFunction) && !($reflector instanceof \ReflectionMethod)) {
            throw new \LogicException('Invalid type of reflector.');
        }
        $this->route            = $route;
        $this->reflector        = $reflector;
        $this->instance         = $instance;
        $this->annotated_method = $this->isFunction() ? null : AnnotatedMethod::of($reflector);
    }
    
    /**
     * Invoke this action
     *
     * @param Request $request
     * @return mixed
     */
    public function invoke(Request $request)
    {
        $vars = $request->attributes;
        $args = [];
        foreach ($this->reflector->getParameters() as $parameter) {
            $name = $parameter->name;
            $type = Reflector::getTypeHint($parameter);
            if (Reflector::typeOf($request, $type)) {
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
                throw new RouteNotFoundException("{$this->route} not found. Routing parameter {$name}(={$origin}) can not convert to {$type}.");
            }
            $args[$name] = $converted;
        }

        return $this->isFunction() ? $this->reflector->invokeArgs($args) : $this->reflector->invokeArgs($this->instance, $args);
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
     * Get method annotation accessor of this route action.
     *
     * @return AnnotatedMethod
     */
    public function getAnnotatedMethod() : AnnotatedMethod
    {
        return $this->annotated_method;
    }

    /**
     * Get given annotation of this route action.
     *
     * @param string $annotation
     * @return void
     */
    public function annotation(string $annotation)
    {
        return $this->annotated_method ? $this->annotated_method->annotation($annotation) : null ;
    }
}

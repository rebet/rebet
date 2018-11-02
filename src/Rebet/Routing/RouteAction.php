<?php
namespace Rebet\Routing;

use Rebet\Annotation\AnnotatedMethod;
use Rebet\Common\Reflector;
use Rebet\Http\Request;
use Rebet\Routing\Route\Route;

/**
 * Route action class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RouteAction
{
    /**
     * ルートオブジェクト
     *
     * @var Route
     */
    private $route = null;
    
    /**
     * 実行対象オブジェクト
     *
     * @var mixed
     */
    private $instance = null;
    
    /**
     * アクションリフレクター
     *
     * @var \ReflectionFunction|\ReflectionMethod
     */
    private $reflector = null;

    /**
     * method annotation accessor
     *
     * @var AnnotatedMethod
     */
    private $annotated_method = null;

    /**
     * ルートアクションオブジェクトを構築します
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
     * アクションを実行します。
     *
     * @param Request $request
     * @return mixed
     */
    public function invoke(Request $request)
    {
        $vars = $request->attributes;
        $args = [];
        foreach ($this->reflector->getParameters() as $parameter) {
            $name          = $parameter->name;
            $optional      = $parameter->isOptional();
            $default_value = $optional ? $parameter->getDefaultValue() : null ;
            $origin        = $vars->has($name) ? $vars->get($name) : $default_value ;
            if (!$optional && $origin === null) {
                throw new RouteNotFoundException("{$this->route} not found. Routing parameter '{$name}' is requierd.");
            }
            $type      = Reflector::getTypeHint($parameter);
            $converted = Reflector::convert($origin, $type);
            if ($origin !== null && $converted === null) {
                throw new RouteNotFoundException("{$this->route} not found. Routing parameter {$name}(={$origin}) can not convert to {$type}.");
            }
            $args[$name] = $converted;
        }

        return $this->isFunction() ? $this->reflector->invokeArgs($args) : $this->reflector->invokeArgs($this->instance, $args);
    }

    /**
     * リフレクターの種別が ReflectionFunction かチェックします。
     *
     * @return boolean
     */
    protected function isFunction() : bool
    {
        return $this->reflector instanceof \ReflectionFunction;
    }

    /**
     * リフレクターの種別が ReflectionMethod かチェックします。
     *
     * @return boolean
     */
    protected function isMethod() : bool
    {
        return $this->reflector instanceof \ReflectionMethod;
    }

    /**
     * このルートアクションのアノテーションアクセッサを取得します。
     *
     * @return AnnotatedMethod
     */
    public function getAnnotatedMethod() : AnnotatedMethod
    {
        return $this->annotated_method;
    }

    /**
     * このルートアクションに紐づいたアノテーションを取得します。
     *
     * @param string $annotation
     * @return void
     */
    public function annotation(string $annotation)
    {
        return $this->annotated_method ? $this->annotated_method->annotation($annotation) : null ;
    }
}

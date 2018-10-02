<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Common\Reflector;

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
     * 実行対象オブジェクト
     *
     * @var mixed
     */
    public $instance = null;
    
    /**
     * アクションリフレクター
     *
     * @var \ReflectionFunction|\ReflectionMethod
     */
    public $reflector = null;

    /**
     * ルートアクションオブジェクトを構築します
     *
     * @param \ReflectionFunction|\ReflectionMethod $reflector
     * @param $mixed $instance
     */
    public function __construct($reflector, $instance = null)
    {
        if (!($reflector instanceof \ReflectionFunction) && !($reflector instanceof \ReflectionMethod)) {
            throw new \LogicException('Invalid type of reflector.');
        }
        $this->reflector = $reflector;
        $this->instance  = $instance;
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
        foreach ($reflector->getParameters() as $parameter) {
            $name = $parameter->name;
            $args[$name] = Reflector::convert($vars->has($name) ? $vars->get($name) : null, Reflector::getTypeHint($parameter));
        }
        
        return $this->isFunction() ? $this->reflector->invokeArgs($args)  : $this->reflector->invokeArgs($this->instance, $args);
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
}

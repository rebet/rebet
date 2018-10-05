<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\StreamedResponse;
use Rebet\Common\Strings;
use Rebet\Common\Inflector;

/**
 * ControllerRoute class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ControllerRoute extends ContractBasedRoute
{
    protected $reflector = null;

    /**
     * ルートオブジェクトを構築します
     *
     * @param array $methods
     * @param string $uri
     * @param callable $action
     * @throws ReflectionException
     */
    public function __construct(string $controller)
    {
        parent::__construct();
        try {
            $this->reflector = new \ReflectionClass($controller);
            $this->namespace = $this->reflector->getNamespaceName();
        } catch (\ReflectionException $e) {
            $this->reflector = new \ReflectionClass($this->namespace.'\\'.$controller);
        }
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @return boolean
     */
    public function match(Request $request): bool
    {
        $request_uri = $request->getRequestUri();
        $uri         = rtrim($this->uri, '/');
        if($request_uri !== $uri && !Strings::startWith($request_uri, "{$uri}/")) {
            return false;
        }

        return parent::match($request);
    }

    /**
     * コントローラー名を取得します。
     *
     * @param bool $with_namespace
     * @return string
     */
    public function getControllerName(bool $with_namespace = true) : string
    {
        return $with_namespace ? $this->reflector->getName() : $this->reflector->getShortName() ;
    }
}

<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\StreamedResponse;

/**
 * ClosureRoute class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ClosureRoute extends Route
{
    /**
     * クロージャ
     *
     * @var \Closure
     */
    protected $action = null;
    
    /**
     * ルートオブジェクトを構築します
     *
     * @param array $methods
     * @param string $uri
     * @param callable $action
     */
    public function __construct(array $methods, string $uri, callable $action)
    {
        parent::__construct($methods, $uri);
        $this->action = \Closure::fromCallable($action);
    }

    /**
     * 実行可能な RouteAction を作成します。
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    public function createRouteAction(Request $request) : RouteAction
    {
        return new RouteAction($this, new \ReflectionFunction($this->action));
    }
    
    /**
     * シャットダウン処理を行います。
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function shutdown(Request $request, Response $response) : void
    {
        // Do Nothing.
    }
}

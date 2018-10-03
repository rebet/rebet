<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\WebResponse;
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
    public $action = null;
    
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
     * Router によってマッチングされたルートが本当に処理可能か検証し、
     * 問題がなければ実行可能な RouteAction を返します。
     * ある種の Route ではアノテーションを用いた追加検証などを実施することができます。
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    public function verify(Request $request) : RouteAction
    {
        return new RouteAction(new ReflectionFunction($this->action));
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

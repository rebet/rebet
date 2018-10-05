<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\StreamedResponse;
use Rebet\Common\Strings;
use Rebet\Common\Utils;

/**
 * Route class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Route
{
    /**
     * ルーティングパラメータ正規表現
     *
     * @var array
     */
    protected $wheres = [];
    
    /**
     * ルートアクション
     *
     * @var RouteAction
     */
    protected $route_action = null;

    /**
     * ルーティングパラメータの正規表現チェックを設定します。
     *
     * @param array|string $name or [$name => $regex, ...]
     * @param string|null $regex
     * @return self
     */
    public function where($name, ?string $regex = null) : self
    {
        foreach (is_array($name) ? $name : [$name => $regex] as $key => $value) {
            $this->wheres[$key] = $value;
        }
        return $this;
    }

    /**
     * 対象のリクエストが自身のルート設定にマッチするかチェックします。
     *
     * なお、マッチングの過程で取り込まれたルーティングパラメータは $request->attributes に格納されます。
     *
     * マッチ結果として false を返すと後続のルート検証が行われます。
     * 後続のルート検証を行わない場合は RouteNotFoundException を throw して下さい。
     * 
     * @param Request $request
     * @return boolean
     * @throws RouteNotFoundException
     */
    abstract public function match(Request $request) : bool ;
    
    /**
     * 実行可能な RouteAction を作成します。
     *
     * @param Request $request
     * @return RouteAction
     */
    abstract protected function createRouteAction(Request $request) : RouteAction ;

    /**
     * ルーティング処理を実行します。
     * 本メソッドは ルートミドルウェアパイプラインの到達先として実行されます。
     *
     * @param Request $request
     * @return Response
     * @throws RouteNotFoundException
     */
    public function handle(Request $request) : Response
    {
        $response = $this->toResponse($request, $this->route_action->invoke($request));
        return $response->prepare($request);
    }

    /**
     * ルートアクションの戻り値をレスポンス形式に変換します。
     *
     * @todo 実装
     *
     * @param Request $request
     * @param mixed $data
     * @return Response
     */
    protected function toResponse(Request $request, $data) : Response
    {
        if ($data instanceof Response) {
            return $data;
        }
        if (is_callable($data)) {
            return new StreamedResponse($data);
        }
        if (is_array($data)) {
            return new JsonResponse($data);
        }
        if ($data instanceof \JsonSerializable) {
            return new JsonResponse($data->jsonSerialize());
        }
        return new BasicResponse($data);
    }

    /**
     * シャットダウン処理を行います。
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    abstract public function terminate(Request $request, Response $response) : void ;

    /**
     * ルートを Pipeline で処理できるようにします。
     *
     * @param Request $request リクエスト
     * @param array $vars 解析済みURI組み込みパラメータ
     */
    public function __invoke(Request $request)
    {
        return $this->handle($request);
    }

    /**
     * このルートのアノテーションアクセッサを取得します。
     *
     * @return AnnotatedMethod
     */
    public function getAnnotatedMethod() : AnnotatedMethod
    {
        return $this->route_action ? $this->route_action->getAnnotatedMethod() : null ;
    }

    /**
     * このルートに紐づいたアノテーションを取得します。
     *
     * @param string $annotation
     * @return void
     */
    public function annotation(string $annotation)
    {
        return $this->route_action ? $this->route_action->annotation($annotation) : null ;
    }
}

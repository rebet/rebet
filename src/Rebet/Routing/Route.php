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
     * ルーティング対象メソッド
     *
     * @var array
     */
    protected $methods = [];

    /**
     * ルーティング対象URI
     *
     * ルーティングパラメータプレースホルダーとして以下の記述が利用できます。
     *
     *  * {name}
     *  * {name?}
     *
     * @var string
     */
    public $uri = null;

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
     * 文字列化します。
     *
     * @return string
     */
    public function __toString()
    {
        return "Route: [".join('|', $this->methods)."] {$this->uri} where ".json_encode($this->wheres);
    }

    /**
     * ルートオブジェクトを構築します
     *
     * @param array $methods
     * @param string $uri
     */
    public function __construct(array $methods, string $uri)
    {
        $this->methods = $methods;
        $this->uri     = $uri;
    }

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
     * 本メソッドは {} プレースホルダによる URI 指定を解析し、マッチ結果を返します。
     * なお、マッチングの過程で取り込まれたルーティングパラメータは $request->attributes に格納されます。
     *
     * マッチ結果として false を返すと後続のルート検証が行われます。
     * 後続のルート検証を行わない場合は RouteNotFoundException を throw して下さい。
     *
     * @param Request $request
     * @return bool
     * @throws RouteNotFoundException
     */
    public function match(Request $request) : bool
    {
        // echo "\npreg_match('{$this->getMatchingRegex()}', '". $request->getRequestUri()."');\n";
        
        $matches  = [];
        $is_match = preg_match($this->getMatchingRegex(), $request->getRequestUri(), $matches);
        if (!$is_match) {
            return false;
        }

        if (!empty($this->methods) && !in_array($request->getMethod(), $this->methods)) {
            throw new RouteNotFoundException("{$this} not found. Invalid method {$request->getMethod()} given.");
        }

        $vars = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                if (Utils::isBlank($value)) {
                    continue;
                }
                $regex = $this->wheres[$key] ?: null ;
                if ($regex && !preg_match($regex, $value)) {
                    throw new RouteNotFoundException("{$this} not found. Routing parameter '{$key}' value '{$value}' not match {$regex}.");
                }
                $vars[$key] = $value;
            }
        }

        $request->attributes->add($vars);
        $this->route_action = $this->createRouteAction($request);
        $request->route = $this;

        $this->verify($request);
        return true;
    }

    /**
     * URI パターンマッチ用の正規表現を返します。
     *
     * @return string
     */
    protected function getMatchingRegex() : string
    {
        $regex = $this->uri;
        $regex = preg_replace('/(\/{[^{]+?\?})/', '(?:\1)?/?', $regex);
        $regex = str_replace('?}', '}', $regex);
        $regex = str_replace('{', '(?P<', $regex);
        $regex = str_replace('}', '>[^/]+?)', $regex);
        $regex = str_replace('/', '\\/', $regex);
        return '/^'.$regex.'$/';
    }
    
    /**
     * 実行可能な RouteAction を作成します。
     *
     * @param Request $request
     * @return RouteAction
     */
    abstract protected function createRouteAction(Request $request) : RouteAction ;

    /**
     * Router によってマッチングされたルートが処理可能か追加検証します。
     * アノテーションを用いた追加検証などを実施する場合は本メソッドをオーバーライドして下さい。
     *
     * @param Request $request
     * @return bool
     * @throws RouteNotFoundException
     */
    protected function verify(Request $request) : void
    {
        // Do nothing.
    }

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
    abstract public function shutdown(Request $request, Response $response) : void ;

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
}

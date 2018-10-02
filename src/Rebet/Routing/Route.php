<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;

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
    public $methods = [];

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
    public $wheres = [];
    
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
        foreach (is_array($name) ? $name : [$name, $regex] as $key => $value) {
            $this->wheres[$key] = $value;
        }
        return $this;
    }
    
    /**
     * 対象のリクエストが自身のルート設定にマッチするかチェックします。
     * 本メソッドは {} プレースホルダによる URI 指定を解析し、マッチ結果を返します。
     * なお、マッチングの過程で取り込まれたルーティングパラメータは $request->attributes に格納されます。
     *
     * @param Request $request
     * @return bool
     */
    public function match(Request $request) : array
    {
        if (!empty($this->methods) && in_array($request->getMethod(), $this->methods)) {
            return false;
        }

        $matches  = [];
        $is_match = preg_match($this->getMatchingRegex(), $request->getBasePath(), $matches);
        if (!$is_match) {
            return false;
        }
        
        $vars = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $regex = $this->wheres[$key] ?: null ;
                if ($regex && !preg_match($regex, $value)) {
                    return false;
                }
                $vars[$key] = $value;
            }
        }

        $request->attributes->add($vars);
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
        $regex = preg_replace('/(\/{[^{]+?\?})/', '(?:\1)?', $regex);
        $regex = str_replace('?}', '}', $regex);
        $regex = str_replace('{', '(?P<', $regex);
        $regex = str_replace('}', '>[^/]+?)', $regex);
        $regex = str_replace('/', '\\/', $regex);
        return '/^'.$regex.'$/';
    }
    
    /**
     * Router によってマッチングされたルートが最終的に処理可能か検証し、
     * 問題がなければ実行可能な RouteAction を返します。
     * ある種の Route ではアノテーションを用いた追加検証などを実施することができます。
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
     */
    abstract public function verify(Request $request) : RouteAction ;

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
        $action = $this->verify($request);
        return $this->toResponse($request, $action->invoke($request));
    }

    /**
     * 様々な形式のアクション戻り値をレスポンス形式に変換します。
     *
     * @todo 実装
     *
     * @param Request $request
     * @param mixed $response
     * @return Response
     */
    protected function toResponse($request, $response) : Response
    {
        // 要実装

        $response = new Response($response);
        return $response->prepare($request);
    }

    /**
     * シャットダウン処理を行います。
     *
     * @return void
     */
    abstract public function shutdown() : void ;

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

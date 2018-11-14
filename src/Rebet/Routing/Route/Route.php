<?php
namespace Rebet\Routing\Route;

use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Routing\RouteAction;
use Rebet\Auth\Annotation\Role;

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
     * The prefix path of this route.
     *
     * @var string
     */
    public $prefix = '';

    /**
     * Middlewares for this route.
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Allow roles can be accessed this route
     *
     * @var array
     */
    protected $roles = ['ALL'];

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
     * @return bool
     * @throws RouteNotFoundException
     */
    public function match(Request $request) : bool
    {
        $vars = $this->analyze($request);
        if ($vars === null) {
            return false;
        }
        $request->attributes->add($vars);
        $request->route     = $this;
        $this->route_action = $this->createRouteAction($request);
        return true;
    }

    /**
     * 指定のリクエストを解析し、自身のルートにマッチするか解析します。
     * 解析の過程で取り込んだルーティングパラメータを返します。
     *
     * 解析結果として null を返すと後続のルート検証が行われます。
     * 後続のルート検証を行わない場合は RouteNotFoundException を throw して下さい。
     *
     * @param Request $request
     * @return array|null
     * @throws RouteNotFoundException
     */
    abstract protected function analyze(Request $request) : ?array;

    /**
     * analyze によってマッチしたリクエストを処理するための ルートアクション を返します。
     * サブクラスではここで追加のアノテーション検証などを行うことができます。
     *
     * 追加検証でルーティング対象外となる場合は RouteNotFoundException を throw して下さい。
     *
     * @param Request $request
     * @return RouteAction
     * @throws RouteNotFoundException
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
        return Responder::toResponse($this->route_action->invoke($request), $request);
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

    /**
     *  Get or set the middlewares attached to the route.
     *
     * @param mixed ...$middlewares
     * @return self|array
     */
    public function middlewares(...$middlewares)
    {
        if (empty($middlewares)) {
            return $this->middlewares;
        }
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     *  Get or set the roles attached to the route.
     *
     * @param mixed ...$roles
     * @return self|array
     */
    public function roles(...$roles)
    {
        if (empty($roles)) {
            $role = $this->annotation(Role::class);
            return $role ? $role->allow : $this->roles ;
        }
        $this->roles = $roles;
        return $this;
    }
}

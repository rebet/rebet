<?php
namespace Rebet\Routing\Route;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\BasicResponse;
use Rebet\Http\JsonResponse;
use Rebet\Http\StreamedResponse;
use Rebet\Common\Strings;
use Rebet\Inflector\Inflector;

/**
 * ControllerRoute class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ControllerRoute extends ConventionalRoute
{
    // public static function defaultConfig()
    // {
    //     return parent::defaultConfig();
    // }

    /**
     * ルーティング対象URI
     *
     * @var string
     */
    public $uri = null;

    /**
     * コントローラアクション
     *
     * @var \ReflectionClass
     */
    protected $action = null;

    /**
     * ルートオブジェクトを構築します
     *
     * @param string $uri
     * @param callable $action
     * @throws ReflectionException
     */
    public function __construct(string $uri, string $controller)
    {
        parent::__construct([]);
        $this->uri = $uri;
        try {
            $this->action    = new \ReflectionClass($controller);
            $this->namespace = $this->action->getNamespaceName();
        } catch (\ReflectionException $e) {
            $this->action    = new \ReflectionClass($this->namespace.'\\'.$controller);
        }
    }

    /**
     * リクエストURIを コントローラー名／アクション名／引数 に分解します。
     *
     * @param string $request_uri
     * @return array
     */
    protected function resolveRequestUri(string $request_uri) : array
    {
        $request_uri        = Strings::ltrim($request_uri, $this->uri);
        $requests           = explode('/', trim($request_uri, '/')) ;
        $part_of_controller = Inflector::snakize(Strings::rtrim($this->action->getShortName(), $this->controller_suffix), $this->uri_snake_separator);
        $part_of_action     = array_shift($requests) ?: $this->default_part_of_action;
        $args               = $requests;
        return [$part_of_controller, $part_of_action, $args];
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
    protected function analyze(Request $request) : ?array
    {
        $request_uri = $request->getRequestUri();
        $uri         = rtrim($this->uri, '/');
        if ($request_uri !== $uri && !Strings::startsWith($request_uri, "{$uri}/")) {
            return null;
        }

        return parent::analyze($request);
    }

    /**
     * コントローラー名を取得します。
     *
     * @param bool $with_namespace
     * @return string
     */
    public function getControllerName(bool $with_namespace = true) : string
    {
        return $with_namespace ? $this->action->getName() : $this->action->getShortName() ;
    }
}

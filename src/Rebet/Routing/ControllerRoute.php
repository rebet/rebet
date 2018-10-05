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
class ControllerRoute extends ConventionalRoute
{
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
        parent::__construct();
        $this->uri = $uri;
        try {
            $this->action    = new \ReflectionClass($controller);
            $this->namespace = $this->action->getNamespaceName();
        } catch (\ReflectionException $e) {
            $this->action    = new \ReflectionClass($this->namespace.'\\'.$controller);
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
        if($request_uri !== $uri && !Strings::startsWith($request_uri, "{$uri}/")) {
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
        return $with_namespace ? $this->action->getName() : $this->action->getShortName() ;
    }
}

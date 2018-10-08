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
 * Declarative Route class
 *
 * 宣言的なルートオブジェクト
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class DeclarativeRoute extends Route
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
     * ルーティングパラメータ解析を行い、対象のリクエストが自身のルートにマッチるか検証します。
     *
     * @param Request $request
     * @return void
     * @throws RouteNotFoundException
     */
    protected function analyze(Request $request)
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
        
        return $vars;
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
}

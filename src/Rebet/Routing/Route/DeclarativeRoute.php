<?php
namespace Rebet\Routing\Route;

use Rebet\Common\Path;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Http\Request;
use Rebet\Routing\Exception\RouteNotFoundException;

/**
 * Declarative Route class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class DeclarativeRoute extends Route
{
    /**
     * Routing target method
     *
     * @var array
     */
    protected $methods = [];

    /**
     * Routing target URI
     *
     * The following format can be use as a routing parameter placeholder.
     *
     *  * {name}  - Required parameter
     *  * {name?} - Optional parameter
     *
     * @var string
     */
    public $uri = null;

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        $rc     = new \ReflectionClass($this);
        $where  = empty($this->wheres) ? '' : ' where '.json_encode($this->wheres);
        $method = empty($this->methods) ? '[ALL]' : "[".join('|', $this->methods)."]" ;
        return $rc->getShortName().": {$method} {$this->uri}{$where}";
    }

    /**
     * Create a declarative route.
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
     * It analyzes the given request and analyzes whether it matches this route.
     * Returns the routing parameters captured during the analysis process.
     *
     * If null is returned as an analysis result, subsequent route verification is performed.
     * Throw RouteNotFoundException if subsequent route verification is not done.
     *
     * @param Request $request
     * @return array|null
     * @throws RouteNotFoundException
     */
    protected function analyze(Request $request) : ?array
    {
        $matches  = [];
        $is_match = preg_match($this->getMatchingRegex(), $request->getRequestPath(), $matches);
        if (!$is_match) {
            return null;
        }

        if (!empty($this->methods) && !in_array($request->getMethod(), $this->methods)) {
            throw RouteNotFoundException::by("{$this} not found. Invalid method {$request->getMethod()} given.");
        }

        $vars = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                if (Utils::isBlank($value)) {
                    continue;
                }
                $regex = $this->wheres[$key] ?? null ;
                if ($regex && !preg_match($regex, $value)) {
                    throw RouteNotFoundException::by("{$this} not found. Routing parameter '{$key}' value '{$value}' not match {$regex}.");
                }
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    /**
     * {@inheritDoc}
     */
    public function defaultView() : string
    {
        return Path::normalize(Strings::latrim($this->uri, '{'));
    }

    /**
     * Get the regex pattern for URI match and capture routing parameters.
     *
     * @return string
     */
    protected function getMatchingRegex() : string
    {
        $regex = $this->prefix.$this->uri;
        $regex = preg_replace('/(\/{[^{]+?\?})/', '(?:\1)?/?', $regex);
        $regex = str_replace('?}', '}', $regex);
        $regex = str_replace('{', '(?P<', $regex);
        $regex = str_replace('}', '>[^/]+?)', $regex);
        $regex = str_replace('/', '\\/', $regex);
        return '/^'.$regex.'$/';
    }
}

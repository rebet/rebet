<?php
namespace Rebet\Http;

use Rebet\Common\Renderable;
use Rebet\Common\Strings;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\JsonResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Http\Response\StreamedResponse;

/**
 * Responder Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Responder
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }
    
    /**
     * Create a response for given data.
     *
     * @param mixed $data
     * @param Request|null (default: null for Request::current())
     * @return Response
     */
    public static function toResponse($data, ?Request $request = null) : Response
    {
        return static::prepare(static::createResponseByTypeOf($data), $request);
    }

    /**
     * Prepare the Response.
     *
     * @param Response $response
     * @param Request|null $request
     * @return Response
     */
    protected static function prepare(Response $response, ?Request $request = null) : Response
    {
        $request = $request ?? Request::current();
        return $request ? $response->prepare($request) : $response ;
    }

    /**
     * Create a response for given data type.
     *
     * @param Request $request
     * @param mixed $data
     * @return Response
     */
    protected static function createResponseByTypeOf($data) : Response
    {
        if ($data instanceof Response) {
            return $data;
        }
        if ($data instanceof Renderable) {
            return new BasicResponse($data->render());
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
     * Create a RedirectResponse from given url and queries.
     * If the given url starts with '/' then append prefix when the route has it.
     * If you do not want this behavior you can use starts with 'http(s)//...' or '@/path/to/page'.
     *
     * @param string $url
     * @param array $query (default: [])
     * @param int $status (default: 302)
     * @param array $headers (default: [])
     * @param Request|null (default: null for Request::current())
     * @return RedirectResponse
     */
    public static function redirect(string $url, array $query = [], int $status = 302, array $headers = [], ?Request $request = null) : RedirectResponse
    {
        $request = $request ?? Request::current() ;
        $url     = empty($query) ? $url : $url.(Strings::contains($url, '?') ? '&' : '?').http_build_query($query) ;
        if (Strings::startsWith($url, '@')) {
            $url = Strings::ltrim($url, '@', 1);
        } else {
            $url = Strings::startsWith($url, '/') ? $request->route->prefix.$url : $url ;
        }
        return static::prepare(new RedirectResponse($url, $status, $headers), $request);
    }
}

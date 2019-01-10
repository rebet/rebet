<?php
namespace Rebet\Http;

use Rebet\Common\Renderable;
use Rebet\Common\Strings;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\JsonResponse;
use Rebet\Http\Response\ProblemResponse;
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
     * @param int $status code of HTTP (default: 200)
     * @param array $headers (default: [])
     * @param Request|null (default: null for Request::current())
     * @return Response
     */
    public static function toResponse($data, int $status = 200, array $headers = [], ?Request $request = null) : Response
    {
        return static::prepare(static::createResponseByTypeOf($data, $status, $headers), $request);
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
     * @param mixed $data
     * @param int $status code of HTTP (default: 200)
     * @param array $headers (default: [])
     * @param Request $request
     * @return Response
     */
    protected static function createResponseByTypeOf($data, int $status = 200, array $headers = []) : Response
    {
        if ($data instanceof Response) {
            return $data;
        }
        if ($data instanceof Renderable) {
            return new BasicResponse($data->render(), $status, $headers);
        }
        if (is_callable($data)) {
            return new StreamedResponse($data, $status, $headers);
        }
        if (is_array($data)) {
            return new JsonResponse($data, $status, $headers);
        }
        if ($data instanceof \JsonSerializable) {
            return new JsonResponse($data->jsonSerialize(), $status, $headers);
        }
        return new BasicResponse($data, $status, $headers);
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
            $url = Strings::startsWith($url, '/') ? $request->getRoutePrefix().$url : $url ;
        }
        return static::prepare(new RedirectResponse($url, $status, $headers), $request);
    }

    /**
     * Create Problem Details (RFC7807 Problem Details for HTTP APIs) Response
     * Note: 'detail' and 'additional' can be set by method chain.
     * Note: You must be set the 'type' of URI reference that identifies the problem type when you want to contain the additional data.
     *
     * @param int $status of HTTP response
     * @param string|null $type of problem (default: 'about:blank')
     * @param string|null $title of problem (default: HTTP status label)
     * @param array $headers of HTTP response (default: [])
     * @param int $encoding_options of JSON encode (default: 0)
     */
    public static function problem(int $status, ?string $type = null, ?string $title = null, array $headers = [], int $encoding_options = 0) : ProblemResponse
    {
        return new ProblemResponse($status, $type, $title, $headers, $encoding_options);
    }
}

<?php
namespace Rebet\Http;

use Rebet\Tools\Template\Renderable;
use Rebet\Tools\Strings;
use Rebet\Filesystem\Storage;
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
            $url = Strings::lcut($url, 1);
        } else {
            $url = Strings::startsWith($url, '/') && $request !== null ? $request->getRoutePrefix().$url : $url ;
        }
        return static::prepare(new RedirectResponse($url, $status, $headers), $request);
    }

    /**
     * Create Problem Response (RFC7807 Problem Details for HTTP APIs).
     *
     * Note: 'detail' and 'additional' can be set by method chain.
     * Note: You must be set the 'type' of URI reference that identifies the problem type when you want to contain the additional data.
     * Note: When the type is TYPE_HTTP_STATUS(='about:blank') then the title SHOULD be the same as the recommended HTTP status phrase, although it MAY be localized.
     *
     * @param int $status of HTTP response
     * @param string|null $title of problem or full transration key (default: HTTP status label)
     * @param string|null $type of problem (default: TYPE_HTTP_STATUS)
     * @param array $headers of HTTP response (default: [])
     * @param int $encoding_options of JSON encode (default: 0)
     * @return ProblemResponse
     */
    public static function problem(int $status, ?string $title = null, ?string $type = null, array $headers = [], int $encoding_options = 0) : ProblemResponse
    {
        return new ProblemResponse($status, $title, $type, $headers, $encoding_options);
    }

    /**
     * Create a streamed response for a given file.
     * NOTE: This moethod automatically create fallback filename using MD5 of ginven filename.
     *
     * @param string $path
     * @param string|null $filename (default: null)
     * @param array $headers (default: [])
     * @param string $disposition (default: 'inline')
     * @param string $disk of filesystem (default: null for use private disk)
     * @return StreamedResponse
     */
    public static function file(string $path, ?string $filename = null, array $headers = [], string $disposition = 'inline', string $disk = null) : StreamedResponse
    {
        $filesystem  = $disk ? Storage::disk($disk) : Storage::private() ;
        $response    = new StreamedResponse();
        $filename    = $filename ?? basename($path);
        $disposition = $response->headers->makeDisposition($disposition, $filename, preg_replace('/^.*\./', md5($filename).'.', $filename));

        $response->headers->replace($headers + [
            'Content-Type'        => $filesystem->mimeType($path),
            'Content-Length'      => $filesystem->size($path),
            'Content-Disposition' => $disposition,
        ]);

        $response->setCallback(function () use ($filesystem, $path) {
            $stream = $filesystem->readStream($path);

            while (!feof($stream)) {
                echo fread($stream, 2048);
            }

            fclose($stream);
        });

        return $response;
    }

    /**
     * Create a streamed download response for a given file.
     * NOTE: This moethod automatically create fallback filename using MD5 of ginven filename.
     *
     * @param string $path
     * @param string|null $filename (default: null)
     * @param array $headers (default: [])
     * @param string $disk of filesystem (default: null)
     * @return StreamedResponse
     */
    public static function download(string $path, ?string $filename = null, array $headers = [], string $disk = null) : StreamedResponse
    {
        return static::file($path, $filename, $headers, 'attachment', $disk);
    }
}

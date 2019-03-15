<?php
namespace Rebet\Http\Middleware;

use Rebet\Common\Nets;
use Rebet\Common\Securities;
use Rebet\Common\Strings;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Exception\TokenMismatchException;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\Session\Session;

/**
 * [Routing Middleware] Verify Csrf Token Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class VerifyCsrfToken
{
    /**
     * @var string[] list of excludes wildcard url pattern.
     */
    protected $excludes;

    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $is_support_xsrf = true;

    /**
     * The XSRF-TOKEN cookie life time.
     *
     * @var int
     */
    protected $xsrf_lifetime;

    /**
     * Create Verify Csrf Token Middleware
     *
     * @param array $excludes (default: [])
     * @param bool $is_support_xsrf (default: true)
     * @param int|string|null $xsrf_lifetime (default: depend on configure 'Rebet\Http\Cookie.expire')
     */
    public function __construct(array $excludes = [], bool $is_support_xsrf = true, $xsrf_lifetime = null)
    {
        $this->excludes        = $excludes;
        $this->is_support_xsrf = $is_support_xsrf;
        $this->xsrf_lifetime   = $xsrf_lifetime;
    }

    /**
     * Handle Verify CSRF Token Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return void
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        if ($this->verifyToken($request)) {
            if ($this->is_support_xsrf) {
                Cookie::set('XSRF-TOKEN', Nets::encodeBase64Url(Securities::encrypt($request->session()->token())), $this->xsrf_lifetime);
            }
            return $next($request);
        }

        throw TokenMismatchException::by("CSRF token mismatch.");
    }

    /**
     * Verify the token.
     *
     * @param Request $request
     * @return boolean
     */
    protected function verifyToken(Request $request) : bool
    {
        // Determine if the HTTP request uses a ‘read’ verb.
        if (in_array($request->getMethod(), ['HEAD', 'GET', 'OPTIONS'])) {
            return true;
        }

        // Determine if the request has a URI that should pass through CSRF verification.
        $request_path = $request->getRequestPath();
        foreach ($this->excludes as $exclude) {
            if (Strings::wildmatch($request_path, $exclude)) {
                return true;
            }
        }

        // Verify the token
        [$key, $value] = $this->getTokenFrom($request);
        if ($request->session()->verifyToken($value, ...Session::analyzeTokenScope($key))) {
            return true;
        }

        return false;
    }

    /**
     * Get CSRF token from given request.
     *
     * @param Request $request
     * @return string[] [key, value]
     */
    protected function getTokenFrom(Request $request) : array
    {
        $token = $request->input('_token')
              ?: $request->header('X-CSRF-TOKEN')
              ?: ($this->is_support_xsrf ? Securities::decrypt(Nets::decodeBase64Url($request->header('X-XSRF-TOKEN') ?? '')) : null)
              ;
        if ($token) {
            return ['_token', $token];
        }

        foreach ($request->input() as $key => $value) {
            if (Strings::startsWith($key, '_token')) {
                return [$key, $value];
            }
        }

        return ['_token', null];
    }
}

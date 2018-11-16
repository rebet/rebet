<?php
namespace Rebet\Http\Cookie;

use Rebet\Common\Utils;
use Rebet\Http\Request;
use Rebet\Routing\Router;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Cookie Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Cookie extends SymfonyCookie
{
    /**
     * Queue a cookie to send with the next response.
     *
     * @var array
     */
    protected static $queued = [];

    /**
     * It checks current request has a given name cookie.
     *
     * @param string $name
     * @return bool
     * @throws LogicException when request has not been initialized.
     */
    public static function has(string $name) : bool
    {
        $request = Request::current();
        if (!$request) {
            throw new \LogicException("Request has not been initialized.");
        }
        return $request->cookies->has($name);
    }

    /**
     * Get the cookie value of given name from current request.
     *
     * @param string $name
     * @param mixed $default (default: null)
     * @return mixed
     * @throws LogicException when request has not been initialized.
     */
    public static function get(string $name, $default = null)
    {
        $request = Request::current();
        if (!$request) {
            throw new \LogicException("Request has not been initialized.");
        }
        return $request->cookies->get($name, $default);
    }

    /**
     * Set the cookie of given parameters to queued for next response.
     *
     * Set a cookie via this method then
     *  - the path   will be set to current route prefix, if it is empty then '/'
     *  - the secure will be set to true
     * defaultly.
     *
     * @param string $name
     * @param string $value
     * @param integer $expire (default: 0)
     * @param string|null $path (default: current route prefix, if it is empty then '/')
     * @param string|null $domain (default: null)
     * @param boolean $secure (default: true)
     * @param boolean $http_only (default: true)
     * @param boolean $raw (default: false)
     * @param string|null $same_site (default: null)
     * @return void
     */
    public static function set(string $name, string $value = null, $expire = 0, ?string $path = null, ?string $domain = null, bool $secure = true, bool $http_only = true, bool $raw = false, ?string $same_site = null) : void
    {
        static::enqueue(new static($name, $value, $expire, $path ?? Utils::evl(Router::current()->prefix ?? null, '/'), $domain, $secure, $http_only, $raw, $same_site));
    }

    /**
     * Set the expiered cookie of given name to queued for next response.
     *
     * @param string $name
     * @param string|null $path (default: current route prefix, if it is empty then '/')
     * @param string|null $domain (default: null)
     * @return void
     */
    public static function remove(string $name, ?string $path = null, ?string $domain = null) : void
    {
        static::enqueue(new static($name, null, -2628000, $path ?? Utils::evl(Router::current()->prefix ?? null, '/'), $domain));
    }

    /**
     * Set the cookie to current response and queued for next response.
     *
     * @param Cookie $cookie
     * @return void
     */
    public static function enqueue(Cookie $cookie) : void
    {
        static::$queued[$cookie->getName()] = $cookie;
    }

    /**
     * Remove the cookie of given name from current response and queued for next response.
     *
     * @param string $name
     * @return void
     */
    public static function dequeue(string $name) : void
    {
        unset(static::$queued[$name]);
    }

    /**
     *  Get the queued cookies for next response.
     *
     * @return array
     */
    public static function queued() : array
    {
        return static::$queued;
    }
}

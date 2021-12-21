<?php
namespace Rebet\Http\Cookie;

use Rebet\Http\Request;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Utility\Strings;
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
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/http.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'expire'    => 0,
            'path'      => fn($path) => (Request::current() ? Request::current()->getRoutePrefix() : '').$path,
            'domain'    => null,
            'secure'    => null,
            'http_only' => true,
            'raw'       => false,
            'samesite'  => self::SAMESITE_LAX,
        ];
    }

    /**
     * Queue a cookie to send with the next response.
     *
     * @var array
     */
    protected static $queued = [];

    /**
     * Clear the cookie queue what for next response.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$queued = [];
    }

    /**
     * Create a Cookie based on Symfony's Cookie::create() default parameters.
     *
     * @param string $name
     * @param string|null $value
     * @param string|int|null $expire (default: depend on configure)
     * @param string|null $path (default: depend on configure)
     * @param string|null $domain (default: depend on configure)
     * @param boolean|null $secure (default: depend on configure)
     * @param boolean|null $http_only (default: depend on configure)
     * @param boolean|null $raw (default: depend on configure)
     * @param string|null $samesite (default: depend on configure)
     */
    public function __construct(string $name, ?string $value = null, $expire = null, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $http_only = null, ?bool $raw = null, ?string $samesite = null)
    {
        parent::__construct(
            $name,
            $value,
            $expire ?? static::config('expire'),
            static::convertPath($path),
            $domain ?? static::config('domain', false),
            $secure ?? static::config('secure', false),
            $http_only ?? static::config('http_only'),
            $raw ?? static::config('raw'),
            $samesite ?? static::config('samesite', false)
        );
    }

    /**
     * Convert the given path using configure Rebet\Http\Cookie.path converter.
     * Note: Defaultly, the path converter will prepend a route prefix to the given path if it is necessary.
     * Note: If you want to use fixed path then give the path starts with '@'. It will be returned the given path without '@' as it is.
     *
     * @param string|null $path
     * @return string
     */
    public static function convertPath(?string $path) : string
    {
        if (Strings::startsWith($path, '@')) {
            return Strings::ltrim($path, '@', 1);
        }
        $path_converter = static::config('path');
        return is_callable($path_converter) ? call_user_func($path_converter, $path ?? '') : ($path ?? $path_converter) ;
    }

    /**
     * {@inheritDoc}
     */
    public static function create(string $name, ?string $value = null, $expire = null, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $http_only = null, ?bool $raw = null, ?string $samesite = null) : parent
    {
        return new static($name, $value, $expire, $path, $domain, $secure, $http_only, $raw, $samesite);
    }

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
            throw new LogicException("Request has not been initialized.");
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
            throw new LogicException("Request has not been initialized.");
        }
        return $request->cookies->get($name, $default);
    }

    /**
     * Set the cookie of given parameters to queued for next response.
     *
     * Set a cookie via this method then
     *  - the path   will be set to current route prefix, if it is empty then '/'
     * defaultly.
     *
     * @param string $name
     * @param string|null $value
     * @param string|int|null $expire (default: depend on configure)
     * @param string|null $path (default: depend on configure)
     * @param string|null $domain (default: depend on configure)
     * @param boolean|null $secure (default: depend on configure)
     * @param boolean|null $http_only (default: depend on configure)
     * @param boolean|null $raw (default: depend on configure)
     * @param string|null $samesite (default: depend on configure)
     * @return void
     */
    public static function set(string $name, ?string $value = null, $expire = null, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $http_only = null, ?bool $raw = null, ?string $samesite = null) : void
    {
        static::enqueue(new static($name, $value, $expire, $path, $domain, $secure, $http_only, $raw, $samesite));
    }

    /**
     * Set the expiered cookie of given name to queued for next response.
     *
     * @param string $name
     * @param string|null $path (default: depend on configure)
     * @param string|null $domain (default: depend on configure)
     * @return void
     */
    public static function remove(string $name, ?string $path = null, ?string $domain = null) : void
    {
        static::enqueue(new static($name, null, 0, $path, $domain));
    }

    /**
     * Set the cookie to queued for next response.
     *
     * @param Cookie $cookie
     * @return void
     */
    public static function enqueue(Cookie $cookie) : void
    {
        static::$queued[$cookie->getName()] = $cookie;
    }

    /**
     * Get and remove the cookie of given name from queued for next response.
     *
     * @param string $name
     * @return self|null
     */
    public static function dequeue(string $name) : ?self
    {
        $cookie = static::peek($name);
        unset(static::$queued[$name]);
        return $cookie;
    }

    /**
     * Get the cookie of given name from queued for next response.
     *
     * @param string $name
     * @return self|null
     */
    public static function peek(string $name) : ?self
    {
        return static::$queued[$name] ?? null;
    }

    /**
     *  Get the queued cookies for next response.
     *
     * @return self[]
     */
    public static function queued() : array
    {
        return static::$queued;
    }
}

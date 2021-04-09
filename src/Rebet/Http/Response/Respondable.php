<?php
namespace Rebet\Http\Response;

use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Response;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Strings;

/**
 * Respondable trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Respondable
{
    /**
     * Get a header from the Response.
     *
     * @param string $key
     * @param bool $first (default: false)
     * @return string|string[]|null
     */
    public function getHeader(string $key, $first = false)
    {
        return $first ? $this->headers->get($key) : Arrays::peel($this->headers->all($key));
    }

    /**
     * Set a header on the Response.
     *
     * @param string $key
     * @param array|string $values
     * @param boolean $replace (default: true)
     * @return Response
     */
    public function setHeader(string $key, $values, bool $replace = true) : Response
    {
        $this->headers->set($key, $values, $replace);
        return $this;
    }

    /**
     * Get the cookie of given name.
     *
     * @param string $name
     * @param string|null $path can contains shell's wildcard (default: '*')
     * @param string|null $domain can contains shell's wildcard (default: '*')
     * @return Cookie|array|null
     */
    public function getCookie(string $name, ?string $path = '*', ?string $domain = '*')
    {
        $path    = Cookie::convertPath($path);
        $cookies = [];
        foreach ($this->headers->getCookies() as $cookie) {
            if (
                    $cookie->getName() === $name
                    && ($cookie->getPath() === $path || Strings::wildmatch($cookie->getPath() ?? '', $path))
                    && ($cookie->getDomain() === $domain || Strings::wildmatch($cookie->getDomain() ?? '', $domain))
                ) {
                $cookies[] = $cookie;
            }
        }
        return Arrays::peel($cookies);
    }

    /**
     * Set the cookie.
     *
     * @param Cookie $cookie
     * @return Response
     */
    public function setCookie(Cookie $cookie) : Response
    {
        $this->headers->setCookie($cookie);
        return $this;
    }
}

<?php
namespace Rebet\Http\Response;

use Rebet\Common\Arrays;
use Rebet\Http\Response;
use Symfony\Component\HttpFoundation\HeaderBag;

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
     * @return string|array|null
     */
    public function getHeader(string $key, $first = false)
    {
        $values = $this->headers->get($key, null, $first);
        if ($values === null || Arrays::count($values) === 0) {
            return null;
        }
        return is_array($values) && count($values) === 1 ? $values[0] : $values ;
    }

    /**
     * Set a header on the Response.
     *
     * @param string $key
     * @param array|string $values
     * @param boolean $replace
     * @return self
     */
    public function setHeader(string $key, $values, bool $replace = true) : Response
    {
        // new HeaderBag
        $this->headers->set($key, $values, $replace);
        return $this;
    }

    /**
     * Get the cookie of given key.
     *
     * @param string $key
     * @param boolean $first (default: true)
     * @return Cookie|array|null
     */
    public function getCookie(string $key, bool $first = true)
    {
        $cookies = [];
        foreach ($this->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $key) {
                if ($first) {
                    return $cookie;
                }
                $cookies[] = $cookie;
            }
        }
        return empty($cookies) ? null : $cookies ;
    }
}

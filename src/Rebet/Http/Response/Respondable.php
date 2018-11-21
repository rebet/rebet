<?php
namespace Rebet\Http\Response;

use Rebet\Http\Response;

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
     * @return string|array|null
     */
    public function getHeader(string $key)
    {
        return $this->headers->get($key);
    }

    /**
     * Set a header on the Response.
     *
     * @param string $key
     * @param array|string $values
     * @param boolean $replace
     * @return Response
     */
    public function setHeader(string $key, $values, bool $replace = true) : Response
    {
        $this->headers->set($key, $values, $replace);
        return $this;
    }
}

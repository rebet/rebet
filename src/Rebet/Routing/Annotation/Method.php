<?php
namespace Rebet\Routing\Annotation;

/**
 * Method Annotation
 *
 * USAGE:
 *  - @Method("GET")            ... same as @Method(allow="GET")
 *  - @Method({"GET", "POST"})  ... same as @Method(allow={"GET","POST"})
 *  - @Method(rejects="GET")
 *  - @Method(rejects={"GET". "POST"})
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class Method
{
    /**
     * @var array of allow HTTP methods GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS.
     */
    public $allows = [];

    /**
     * @var array of reject HTTP methods GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS.
     */
    public $rejects = [];

    /**
     * Check acceptable the given method.
     * NOTE: If an allow list is configured, the reject list will be ignored.
     *
     * @param string $method
     * @return boolean
     */
    public function allow(string $method) : bool
    {
        $method = strtoupper($method);
        return !empty($this->allows) ? in_array($method, $this->allows) : !in_array($method, $this->rejects) ;
    }

    /**
     * Check acceptable the given method.
     * NOTE: If an allow list is configured, the reject list will be ignored.
     *
     * @param string $method
     * @return boolean
     */
    public function reject(string $method) : bool
    {
        return !$this->allow($method);
    }
}

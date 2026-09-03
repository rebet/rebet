<?php
namespace Rebet\Routing\Attribute;

/**
 * Method Attribute
 *
 * USAGE:
 *  - #[Method("GET")]
 *  - #[Method("GET", "POST")]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Method
{
    /**
     * @var array<string> of allow HTTP methods GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS.
     */
    public array $allows = [];

    /**
     * Create Method attribute.
     *
     * @param string ...$allows of allow HTTP methods GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS.
     */
    public function __construct(string ...$allows)
    {
        $this->allows = $allows;
    }

    /**
     * Check acceptable the given method.
     * NOTE: If no allow method is configured, any method will be allowed.
     *
     * @param string $method
     * @return boolean
     */
    public function allow(string $method) : bool
    {
        $method = strtoupper($method);
        return empty($this->allows) ? true : in_array($method, $this->allows) ;
    }

    /**
     * Check acceptable the given method.
     * NOTE: If no allow method is configured, any method will be allowed.
     *
     * @param string $method
     * @return boolean
     */
    public function reject(string $method) : bool
    {
        return !$this->allow($method);
    }
}

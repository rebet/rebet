<?php
namespace Rebet\Routing\Annotation;

/**
 * Surface Annotation
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class Surface
{
    /**
     * @var array
     */
    public $allows;

    /**
     * Constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->allows = (array)($values['value']);
    }

    /**
     * Check acceptable the given surface.
     *
     * @param string $surface
     * @return boolean
     */
    public function allow(string $surface) : bool
    {
        return in_array($surface, $this->allows);
    }
}

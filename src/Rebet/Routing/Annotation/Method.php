<?php
namespace Rebet\Routing\Annotation;

/**
 * Method Annotation
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
     * @var array
     * @Enum({"GET","HEAD","POST","PUT","PATCH","DELETE","OPTIONS"})
     */
    public $allows;

    /**
     * @var array
     * @Enum({"GET","HEAD","POST","PUT","PATCH","DELETE","OPTIONS"})
     */
    public $rejects;

    /**
     * Constructor.
     *
     * @param array $values value or [allows, rejects]
     */
    public function __construct(array $values)
    {
        $this->allows  = (array)($values['allows'] ?? $values['value']) ;
        $this->rejects = empty($this->allows) ? (array)($values['rejects']) : [] ;
    }

    /**
     * Check acceptable the given surface.
     *
     * @param string $surface
     * @return boolean
     */
    public function allow(string $surface) : bool
    {
        return empty($this->rejects) ? in_array($surface, $this->allows) : !in_array($surface, $this->rejects) ;
    }
}

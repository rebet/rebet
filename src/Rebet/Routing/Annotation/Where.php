<?php
namespace Rebet\Routing\Annotation;

/**
 * Where Annotation
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class Where
{
    /**
     * @var array
     */
    public $wheres;

    /**
     * Constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->wheres = (array)($values['value']) ;
    }
}

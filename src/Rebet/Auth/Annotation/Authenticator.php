<?php
namespace Rebet\Auth\Annotation;

/**
 * Authenticator Annotation
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class Authenticator
{
    /**
     * @var string
     */
    public $name;

    /**
     * Constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->name = $values['value'] ;
    }
}
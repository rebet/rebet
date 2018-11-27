<?php
namespace Rebet\Auth\Annotation;

/**
 * Role Annotation
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class Role
{
    /**
     * @var array
     */
    public $value;
}

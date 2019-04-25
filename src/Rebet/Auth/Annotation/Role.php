<?php
namespace Rebet\Auth\Annotation;

/**
 * Role Annotation
 *
 * USAGE:
 *  - @Role("all")
 *  - @Role("user")
 *  - @Role({"user", "admin"})
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
     * @var array of acceptable role names
     */
    public $names = [];
}

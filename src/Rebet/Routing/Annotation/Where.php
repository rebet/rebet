<?php
namespace Rebet\Routing\Annotation;

/**
 * Where Annotation
 *
 * USAGE:
 *  - @Where({"seq": "[0-9]+", "code": "[a-zA-Z]+"})
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class Where
{
    /**
     * @var array of where conditions, key is parameter name and value is acceptable regex.
     */
    public $wheres = [];
}

<?php
namespace Rebet\Middleware\Routing;

use Rebet\Tools\Utility\Strings;

/**
 * [Routing Middleware] Trim Strings Middleware Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TrimStrings extends InputDataTransform
{
    /**
     * {@inheritDoc}
     */
    protected function transform($key, $value)
    {
        return is_string($value) ? Strings::mbtrim($value) : $value ;
    }
}

<?php
namespace Rebet\Routing;

/**
 * Route Not Found Exception Class
 *
 * It is thrown if the target route can not be found.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RouteNotFoundException extends \RuntimeException
{
    /**
     * {@inheritDoc}
     */
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

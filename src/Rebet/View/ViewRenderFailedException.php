<?php
namespace Rebet\View;

/**
 * View Render Failed Exception Class
 *
 * It is thrown if the view can not be render.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ViewRenderFailedException extends \RuntimeException
{
    /**
     * {@inheritDoc}
     */
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

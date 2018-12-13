<?php
namespace Rebet\View\Exception;

use Rebet\Common\Exception\RuntimeException;

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
class ViewRenderFailedException extends RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null, int $code = 500)
    {
        parent::__construct($message, $previous, $code);
    }
}

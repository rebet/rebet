<?php
namespace Rebet\Routing;

use Rebet\Http\ProblemRespondable;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;

/**
 * Page Not Found Exception Class
 *
 * It is thrown if the target page can not be display.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PageNotFoundException extends \RuntimeException implements ProblemRespondable
{
    /**
     * {@inheritDoc}
     */
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * {@inheritDoc}
     */
    public function problem() : ProblemResponse
    {
        return Responder::problem(404)->detail($this->getMessage());
    }
}

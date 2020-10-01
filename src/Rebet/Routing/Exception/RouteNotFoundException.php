<?php
namespace Rebet\Routing\Exception;

use Rebet\Tools\Exception\RuntimeException;
use Rebet\Http\ProblemRespondable;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tools\Translation\Translator;

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
class RouteNotFoundException extends RuntimeException implements ProblemRespondable
{
    /**
     * {@inheritDoc}
     */
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }

    /**
     * {@inheritDoc}
     */
    public function problem() : ProblemResponse
    {
        return Responder::problem(404)->detail(Translator::get('message.http.404.detail') ?? $this->getMessage());
    }
}

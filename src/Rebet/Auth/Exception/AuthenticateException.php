<?php
namespace Rebet\Auth\Exception;

use Rebet\Http\ProblemRespondable;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tools\Exception\RuntimeException;
use Rebet\Tools\Translation\Translator;

/**
 * Authenticate Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AuthenticateException extends RuntimeException implements ProblemRespondable
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }

    /**
     * {@inheritDoc}
     */
    public function problem() : ProblemResponse
    {
        return Responder::problem(403)->detail(Translator::get('message.http.403.detail') ?? $this->getMessage());
    }
}

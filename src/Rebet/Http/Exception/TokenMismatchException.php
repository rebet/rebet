<?php
namespace Rebet\Http\Exception;

use Rebet\Http\ProblemRespondable;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tools\Exception\RuntimeException;
use Rebet\Tools\Translation\Translator;

/**
 * Token Mismatch Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TokenMismatchException extends RuntimeException implements ProblemRespondable
{
    /**
     * Create Token Mismatch Exception.
     *
     * @param string $message
     * @param \Throwable $previous (default: null)
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
        return Responder::problem(400)->detail(Translator::get('message.http.400.detail') ?? $this->getMessage());
    }
}

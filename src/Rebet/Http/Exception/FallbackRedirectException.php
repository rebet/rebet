<?php
namespace Rebet\Http\Exception;

use Rebet\Tools\Exception\RuntimeException;
use Rebet\Http\ProblemRespondable;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Tools\Translation\Translator;

/**
 * Fallback Redirect Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FallbackRedirectException extends RuntimeException implements ProblemRespondable
{
    /**
     * Fallback url when some error occurred.
     *
     * @var string
     */
    protected $fallback = null;

    /**
     * Input data for fallback.
     *
     * @var array
     */
    protected $input = [];

    /**
     * Errors cause of fallback.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Create Fallback Exception.
     *
     * @param string $message
     * @param \Throwable $previous (default: null)
     */
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }

    /**
     * Set the fallback URL.
     *
     * @param string $fallback
     * @return self
     */
    public function to(string $fallback) : self
    {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * Set input.
     *
     * @return array
     */
    public function with(array $input) : self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Set errors.
     *
     * @return array
     */
    public function errors(array $errors) : self
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Get redirect response for this fallback
     *
     * @return RedirectResponse
     */
    public function redirect() : RedirectResponse
    {
        return Responder::redirect($this->fallback)->with($this->input)->errors($this->errors);
    }

    /**
     * {@inheritDoc}
     */
    public function problem() : ProblemResponse
    {
        return Responder::problem(
            400,
            Translator::get('message.fallback_errors.title') ?? 'A retryable error occurred. Please check error details and try again.',
            ProblemResponse::TYPE_FALLBACK_ERRORS
        )
        ->detail(Translator::get('message.fallback_errors.detail') ?? $this->getMessage())
        ->additional([
            'errors' => $this->errors,
            'input'  => $this->input,
        ]);
    }
}

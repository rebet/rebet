<?php
namespace Rebet\Http\Exception;

use Rebet\Common\Exception\RuntimeException;
use Rebet\Http\ProblemRespondable;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Http\Response\RedirectResponse;

/**
 * Fallback Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FallbackException extends RuntimeException implements ProblemRespondable
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
     *
     * @todo Fix version of URI on official release or move the spec document to new repository.
     */
    public function problem() : ProblemResponse
    {
        return Responder::problem(
            400,
            'https://github.com/rebet/rebet/blob/master/spec/problem-details/fallback-errors.md',
            'A retryable error occurred. Please check error details and try again.'
        )
        ->detail($this->getMessage())
        ->additional([
            'errors' => $this->errors,
            'input'  => $this->input,
        ]);
    }
}

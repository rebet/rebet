<?php
namespace Rebet\Http;

/**
 * Fallback Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FallbackException extends \RuntimeException
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
     * @param string $fallback
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function __construct(string $fallback, $message = 'Fallback error occurred', $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->fallback = $fallback;
    }

    /**
     * Create Fallback Exception.
     *
     * @param string $fallback
     * @return self
     */
    public static function to(string $fallback) : self
    {
        return new static($fallback);
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
     * @return Response
     */
    public function redirect() : Response
    {
        return Responder::redirect($this->fallback)->with($this->input)->errors($this->errors);
    }
}

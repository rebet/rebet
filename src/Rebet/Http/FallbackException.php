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
     * Errors cause of fallback.
     *
     * @var array
     */
    private $errors = null;

    /**
     * Fallback url when some error occurred.
     *
     * @var string
     */
    private $fallback_url = null;

    /**
     * Request when error occured.
     *
     * @var Request
     */
    private $request = null;

    /**
     * Create Fallback Exception.
     *
     * @param Request $request
     * @param array $errors
     * @param string $fallback_url
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function __construct(Request $request, array $errors, string $fallback_url, $message = 'Validation error occurred', $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request      = $request;
        $this->errors       = $errors;
        $this->fallback_url = $fallback_url;
    }

    /**
     * Get errors.
     *
     * @return array
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Get fallback url.
     *
     * @return string
     */
    public function getFallbackUrl() : string
    {
        return $this->fallback_url;
    }

    /**
     * Get request when error occured.
     *
     * @return Request
     */
    public function getRequest() : Request
    {
        return $this->request;
    }
}

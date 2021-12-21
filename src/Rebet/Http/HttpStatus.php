<?php
namespace Rebet\Http;

use Rebet\Http\Exception\HttpException;
use Rebet\Tools\Config\Configurable;

/**
 * Http Status Class
 *
 * @see https://tools.ietf.org/html/rfc7807
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class HttpStatus
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/http.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'reason_phrases' => [
                100 => 'Continue',
                101 => 'Switching Protocols',
                102 => 'Processing',
                103 => 'Early Hints',
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                207 => 'Multi-Status',
                208 => 'Already Reported',
                226 => 'IM Used',
                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                307 => 'Temporary Redirect',
                308 => 'Permanent Redirect',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Timeout',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Payload Too Large',
                414 => 'URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Range Not Satisfiable',
                417 => 'Expectation Failed',
                418 => "I'm a teapot",
                421 => 'Misdirected Request',
                422 => 'Unprocessable Entity',
                423 => 'Locked',
                424 => 'Failed Dependency',
                426 => 'Upgrade Required',
                451 => 'Unavailable For Legal Reasons',
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                504 => 'Gateway Timeout',
                505 => 'HTTP Version Not Supported',
                506 => 'Variant Also Negotiates',
                507 => 'Insufficient Storage',
                508 => 'Loop Detected',
                509 => 'Bandwidth Limit Exceeded',
                510 => 'Not Extended',
                511 => 'Network Authentication Required',
            ],
        ];
    }

    /**
     * 1xx (Informational) class
     */
    const INFORMATIONAL = 1;

    /**
     * 2xx (Successful) class
     */
    const SUCCESSFUL    = 2;

    /**
     * 3xx (Redirection) class
     */
    const REDIRECTION   = 3;

    /**
     * 4xx (Client Error) class
     */
    const CLIENT_ERROR  = 4;

    /**
     * 5xx (Server Error) class
     */
    const SERVER_ERROR  = 5;

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get the Reason-Phrase of given status code.
     *
     * @param integer $status
     * @return string|null return null when not exists status was given
     */
    public static function reasonPhraseOf(int $status) : ?string
    {
        return static::config("reason_phrases")[$status] ?? null ;
    }

    /**
     * It checks the given status code exists.
     *
     * @param integer $status
     * @return boolean
     */
    public static function exists(int $status) : bool
    {
        return static::reasonPhraseOf($status) !== null ;
    }

    /**
     * Get the HTTP status code class.
     *
     * @param int $status
     * @return int|null return null when not exists status was given
     */
    public static function classOf(int $status) : ?int
    {
        if (!static::exists($status)) {
            return null;
        }
        return (int)($status / 100);
    }

    /**
     * It checks the given status is informational (1xx).
     *
     * @param integer $status
     * @return boolean
     */
    public static function isInformational(int $status) : bool
    {
        return static::classOf($status) === static::INFORMATIONAL;
    }

    /**
     * It checks the given status is Successful (2xx).
     *
     * @param integer $status
     * @return boolean
     */
    public static function isSuccessful(int $status) : bool
    {
        return static::classOf($status) === static::SUCCESSFUL;
    }

    /**
     * It checks the given status is Redirection (3xx).
     *
     * @param integer $status
     * @return boolean
     */
    public static function isRedirection(int $status) : bool
    {
        return static::classOf($status) === static::REDIRECTION;
    }

    /**
     * It checks the given status is Client Error (4xx).
     *
     * @param integer $status
     * @return boolean
     */
    public static function isClientError(int $status) : bool
    {
        return static::classOf($status) === static::CLIENT_ERROR;
    }

    /**
     * It checks the given status is Server Error (5xx).
     *
     * @param integer $status
     * @return boolean
     */
    public static function isServerError(int $status) : bool
    {
        return static::classOf($status) === static::SERVER_ERROR;
    }

    /**
     * Immediately abort HTTP request handling by throws HttpException.
     *
     * @param int $status code of HTTP
     * @param string|null $detail message or full transration key (default: null)
     * @param string|null $title (default: Basic HTTP status label)
     * @param \Throwable $previous (default: null)
     * @return void
     * @throws HttpException of given HTTP status code.
     */
    public static function abort(int $status, ?string $detail = null, ?string $title = null, ?\Throwable $previous = null) : void
    {
        throw new HttpException($status, $detail, $title, $previous);
    }
}

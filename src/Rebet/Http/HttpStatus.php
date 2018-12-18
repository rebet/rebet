<?php
namespace Rebet\Http;

use Rebet\Config\Configurable;

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

    public static function defaultConfig() : array
    {
        return [
            'http_status' => [
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
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get the basic label of given status code.
     *
     * @param integer $status
     * @return string|null
     */
    public static function label(int $status) : ?string
    {
        return static::config("http_status.{$status}", false);
    }

    /**
     * It checks the given status code exists.
     *
     * @param integer $status
     * @return boolean
     */
    public static function exists(int $status) : bool
    {
        return static::label($status) !== null ;
    }
}

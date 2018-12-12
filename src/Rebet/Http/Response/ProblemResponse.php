<?php
namespace Rebet\Http\Response;

use Rebet\Config\Configurable;
use Rebet\Http\Response;

/**
 * Problem Response (RFC7807 Problem Details for HTTP APIs) Class
 *
 * @see https://tools.ietf.org/html/rfc7807
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ProblemResponse extends JsonResponse
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

    const TYPE_NO_ADDITIONAL = 'about:blank';

    /**
     * @var array of problem
     */
    protected $problem = [];

    /**
     * Create Problem Response (RFC7807 Problem Details for HTTP APIs).
     * Note: 'detail' and 'additional' can be set by method chain.
     * Note: You must be set the 'type' of URI reference that identifies the problem type when you want to contain the additional data.
     *
     * @param int $status of HTTP response
     * @param string|null $type of problem (default: 'about:blank')
     * @param string|null $title of problem (default: HTTP status label)
     * @param array $headers of HTTP response (default: [])
     * @param int $encoding_options of JSON encode (default: 0)
     */
    public function __construct(int $status, ?string $type = null, ?string $title = null, array $headers = [], int $encoding_options = 0)
    {
        $status_label = static::config("http_status.{$status}". false);
        if ($status_label === null) {
            throw new \LogicException("Invalid http status code [{$status}]");
        }
        $title         = $title ?? $status_label;
        $type          = $type ?? static::TYPE_NO_ADDITIONAL;
        $this->problem = [
            'status' => $status,
            'title'  => $title,
            'type'   => $type,
        ];
        $headers = array_merge($headers, ['Content-Type: application/problem+json']);
        parent::__construct($this->problem, $status, $headers, $encoding_options);
    }

    /**
     * Set the detail message.
     *
     * @param string $detail
     * @return self
     */
    public function detail(string $detail) : self
    {
        $this->problem['detail'] = $detail;
        $this->setData($this->problem);
        return $this;
    }

    /**
     * Set the additional data.
     *
     * @param string|array $key
     * @param mixed $value (default: null)
     * @return self
     */
    public function additional($key, $value = null) : self
    {
        if ($this->problem['type'] === static::TYPE_NO_ADDITIONAL) {
            throw new \LogicException("The type of 'about:blank' can not contains additional.");
        }
        if (is_array($key)) {
            unset($key['status'], $key['title'], $key['type'], $key['detail']);
            $this->problem = array_merge($this->problem, $key);
        } else {
            $this->problem[$key] = $value;
        }
        $this->setData($this->problem);
        return $this;
    }
}

<?php
namespace Rebet\Http\Response;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Reflector;
use Rebet\Http\Http;
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
        $status_label = HttpStatus::reasonPhraseOf($status);
        if ($status_label === null) {
            throw LogicException::by("Invalid http status code [{$status}]");
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
            throw LogicException::by("The type of 'about:blank' can not contains additional.");
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
    
    /**
     * Get the problem details data of given key.
     *
     * @param string $key
     * @return mixed
     */
    public function getProblem(?string $key = null)
    {
        return Reflector::get($this->problem, $key);
    }
}

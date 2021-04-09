<?php
namespace Rebet\Http\Response;

use Rebet\Http\HttpStatus;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Translation\Translator;

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
    /**
     * @var array of reserved words of RFC7807 Problem Details for HTTP APIs
     */
    protected const RESERVED_WORDS = ['status', 'type', 'title', 'detail', 'instance'];

    /**
     * This problem type indicates that the problem has no additional semantics beyond that of the HTTP status code.
     * When this type is used, the title SHOULD be the same as the recommended HTTP status phrase for that code (e.g., "Not Found" for 404, and so on), although it MAY be localized.
     *
     * @var string
     */
    const TYPE_HTTP_STATUS = 'about:blank';

    /**
     * This problem type indicates that the fallback error problem like validation error.
     * Usearly, this type has additional information 'errors' what cased of fallback and 'input' what source of errors.
     *
     * @todo Fix version of URI on official release or move the spec document to new repository.
     * @see https://github.com/rebet/rebet/blob/master/spec/problem-details/fallback-errors.md
     * @var string
     */
    const TYPE_FALLBACK_ERRORS = 'https://github.com/rebet/rebet/blob/master/spec/problem-details/fallback-errors.md';

    /**
     * @var array of problem
     */
    protected $problem = [];

    /**
     * Create Problem Response (RFC7807 Problem Details for HTTP APIs).
     *
     * Note: 'detail' and 'additional' can be set by method chain.
     * Note: You must be set the 'type' of URI reference that identifies the problem type when you want to contain the additional data.
     * Note: When the type is TYPE_HTTP_STATUS(='about:blank') then the title SHOULD be the same as the recommended HTTP status phrase, although it MAY be localized.
     *
     * @param int $status of HTTP response
     * @param string|null $title of problem or full transration key (default: HTTP status label)
     * @param string|null $type of problem (default: TYPE_HTTP_STATUS)
     * @param array $headers of HTTP response (default: [])
     * @param int $encoding_options of JSON encode (default: 0)
     */
    public function __construct(int $status, ?string $title = null, ?string $type = null, array $headers = [], int $encoding_options = 0)
    {
        $status_label = HttpStatus::reasonPhraseOf($status);
        if ($status_label === null) {
            throw new LogicException("Invalid http status code [{$status}]");
        }
        $this->problem = [
            'status' => $status,
            'title'  => Translator::get($title) ?? $title ?? Translator::get("message.http.{$status}.title") ?? $status_label,
            'type'   => $type ?? static::TYPE_HTTP_STATUS,
        ];
        parent::__construct($this->problem, $status, $headers, $encoding_options);
        $this->setHeader('Content-Type', 'application/problem+json');
    }

    /**
     * Set the detail that a human-readable explanation specific to this occurrence of the problem.
     *
     * @param string $detail message or full transration key
     * @return self
     */
    public function detail(string $detail) : self
    {
        $this->problem['detail'] = Translator::get($detail) ?? $detail;
        $this->setData($this->problem);
        return $this;
    }

    /**
     * Set the instance what a URI reference that identifies the specific occurrence of the problem.
     * It may or may not yield further information if dereferenced.
     *
     * @param string $instance  what a URI reference that identifies the specific occurrence of the problem.
     * @return self
     */
    public function instance(string $instance) : self
    {
        $this->problem['instance'] = $instance;
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
        if ($this->problem['type'] === static::TYPE_HTTP_STATUS) {
            throw new LogicException("The type of 'about:blank' can not contains additional.");
        }
        if (is_array($key)) {
            foreach (static::RESERVED_WORDS as $reserved) {
                if (array_key_exists($reserved, $key)) {
                    throw new LogicException("The key of '{$reserved}' is reserved. so you can't set '{$reserved}' via additional.");
                }
            }
            $this->problem = array_merge($this->problem, $key);
        } else {
            if (in_array($key, static::RESERVED_WORDS, true)) {
                throw new LogicException("The key of '{$key}' is reserved. so you can't set '{$key}' via additional.");
            }
            $this->problem[$key] = $value;
        }
        $this->setData($this->problem);
        return $this;
    }

    /**
     * Get the problem details data of given key.
     *
     * @param string $key can contains dot notation (default: null)
     * @return mixed
     */
    public function getProblem(?string $key = null)
    {
        return Reflector::get($this->problem, $key);
    }
}

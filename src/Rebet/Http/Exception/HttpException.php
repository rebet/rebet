<?php
namespace Rebet\Http\Exception;

use Rebet\Tools\Exception\RuntimeException;
use Rebet\Http\HttpStatus;
use Rebet\Http\ProblemRespondable;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Translation\Translator;

/**
 * Http Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class HttpException extends RuntimeException implements ProblemRespondable
{
    /**
     * @var int HTTP status code.
     */
    protected $status;

    /**
     * @var string HTTP error title.
     */
    protected $title;

    /**
     * @var string|null HTTP error detail.
     */
    protected $detail;

    /**
     * Http Exception.
     *
     * @param int $status code of HTTP
     * @param string|null $detail message or full transration key (default: null)
     * @param string|null $title or full transration key (default: Basic HTTP status label)
     * @param \Throwable $previous (default: null)
     */
    public function __construct(int $status, ?string $detail = null, ?string $title = null, ?\Throwable $previous = null)
    {
        $this->status = $status;
        $this->detail($detail);
        $this->title($title);
        $status_label = $title ? $this->title : "{$this->status} {$this->title}" ;
        $message      = $detail ? "{$status_label}: {$this->detail}" : $status_label ;
        parent::__construct($message, $previous);
    }

    /**
     * Get HTTP status code.
     *
     * @return integer
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * Set HTTP error title.
     *
     * @param string|null $title or full transration key (default: Basic HTTP status label)
     * @return self
     */
    public function title(?string $title) : self
    {
        $this->title = Translator::get($title) ?? $title ?? Translator::get("message.http.{$this->status}.title") ?? HttpStatus::reasonPhraseOf($this->status) ?? 'Unknown Error';
        return $this;
    }

    /**
     * Get HTTP error title.
     *
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * Set HTTP error detail.
     *
     * @param string|null $detail message or full transration key (default: null)
     * @return self
     */
    public function detail(?string $detail) : self
    {
        $this->detail = Translator::get($detail) ?? $detail ;
        return $this;
    }

    /**
     * Get HTTP error detail.
     *
     * @return string|null
     */
    public function getDetail() : ?string
    {
        return $this->detail;
    }

    /**
     * {@inheritDoc}
     */
    public function problem() : ProblemResponse
    {
        return Responder::problem($this->status, $this->title)->detail($this->detail);
    }
}

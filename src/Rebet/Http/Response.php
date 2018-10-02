<?php
namespace Rebet\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * Response interface
 *
 * This interface covered Symfony\Component\HttpFoundation\Response of symfony/http-foundation ver 4.1.
 *
 * @see https://github.com/symfony/http-foundation/blob/master/Response.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Response
{
    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102;            // RFC2518
    const HTTP_EARLY_HINTS = 103;           // RFC8297
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207;          // RFC4918
    const HTTP_ALREADY_REPORTED = 208;      // RFC5842
    const HTTP_IM_USED = 226;               // RFC3229
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
    const HTTP_MISDIRECTED_REQUEST = 421;                                         // RFC7540
    const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    const HTTP_LOCKED = 423;                                                      // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918

    public static function create($content = '', $status = 200, $headers = array());
    public function __toString();
    public function __clone();
    public function prepare(Request $request);
    public function sendHeaders();
    public function sendContent();
    public function send();
    public function setContent($content);
    public function getContent();
    public function setProtocolVersion(string $version);
    public function getProtocolVersion(): string;
    public function setStatusCode(int $code, $text = null);
    public function getStatusCode(): int;
    public function setCharset(string $charset);
    public function getCharset(): ?string;
    public function isCacheable(): bool;
    public function isFresh(): bool;
    public function isValidateable(): bool;
    public function setPrivate();
    public function setPublic();
    public function setImmutable(bool $immutable = true);
    public function isImmutable(): bool;
    public function mustRevalidate(): bool;
    public function getDate(): ?\DateTimeInterface;
    public function setDate(\DateTimeInterface $date);
    public function getAge(): int;
    public function expire();
    public function getExpires(): ?\DateTimeInterface;
    public function setExpires(\DateTimeInterface $date = null);
    public function getMaxAge(): ?int;
    public function setMaxAge(int $value);
    public function setSharedMaxAge(int $value);
    public function getTtl(): ?int;
    public function setTtl(int $seconds);
    public function setClientTtl(int $seconds);
    public function getLastModified(): ?\DateTimeInterface;
    public function setLastModified(\DateTimeInterface $date = null);
    public function getEtag(): ?string;
    public function setEtag(string $etag = null, bool $weak = false);
    public function setCache(array $options);
    public function setNotModified();
    public function hasVary(): bool;
    public function getVary(): array;
    public function setVary($headers, bool $replace = true);
    public function isNotModified(Request $request): bool;
    public function isInvalid(): bool;
    public function isInformational(): bool;
    public function isSuccessful(): bool;
    public function isRedirection(): bool;
    public function isClientError(): bool;
    public function isServerError(): bool;
    public function isOk(): bool;
    public function isForbidden(): bool;
    public function isNotFound(): bool;
    public function isRedirect(string $location = null): bool;
    public function isEmpty(): bool;
    public static function closeOutputBuffers(int $targetLevel, bool $flush);
}
